<?php

namespace NewCMS\Controllers;

use NewCMS\Domain\NewComplexOrder;
use NewCMS\Libs\NewBasket;
use NewCMS\Repositories\NewComplexOrderRepository;
use NewCMS\Views\ArticlesBaseView;
use NewCMS\Views\CMSBaseView;
use Symfony\Component\HttpFoundation\Request;
use TRMEngine\DiContainer\TRMDIContainer;
use TRMEngine\EMail\Exceptions\TRMEMailExceptions;
use TRMEngine\EMail\Exceptions\TRMEMailSendingExceptions;
use TRMEngine\EMail\TRMEMail;
use TRMEngine\Helpers\TRMLib;
use Exception;

/**
 * контроллер для работы с корзиной товаров
 */
class NewBasketController extends BaseController
{
  /**
   * кодировка по упмолчанию, 
   * а так же в этой кодировке приходят данные из формы через POST от клиентов
   */
  const DEFAULT_CHARSET = "utf-8";
  /**
   * @var NewBasket - экземпляр объекта корзины
   */
  protected $CurrentBasket;
  /**
   * @type string - сохраняется сообщения при обработке и попытке отправить заказ из корзины
   */
  private $StatusText;

  const EmptyBasketText = "Корзина пуста";
  /**
   * интервал между отправками заявок/сообщений через форму корзины, секунды (анти-спам)
   */
  const SUBMIT_INTERVAL_SECONDS = 180;
  /**
   * ключ в $_SESSION для хранения абсолютного Unix-времени следующей разрешенной отправки
   */
  const SESSION_NEXT_SUBMIT_KEY = "NewBasketNextSubmitTime";

  private function getOrderRepository(): NewComplexOrderRepository
  {
    return $this->_RM->getRepository(NewComplexOrder::class);
  }

  /**
   * стартует PHP-сессию, если она еще не запущена
   * (обычно сессия уже запущена в NewLoggerMiddleware)
   */
  private function ensureSessionStarted(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  /**
   * @return int - абсолютное Unix-время следующей разрешенной отправки
   * из сессии пользователя (0 - ограничения нет)
   */
  private function getNextSubmitTime(): int
  {
    $this->ensureSessionStarted();
    return isset($_SESSION[self::SESSION_NEXT_SUBMIT_KEY])
      ? (int)$_SESSION[self::SESSION_NEXT_SUBMIT_KEY]
      : 0;
  }

  /**
   * @return int - сколько секунд осталось до разрешенной отправки (0 - можно отправлять)
   */
  private function getRemainingSubmitSeconds(): int
  {
    $Remaining = $this->getNextSubmitTime() - time();
    return ($Remaining > 0) ? $Remaining : 0;
  }

  /**
   * устанавливает в сессии время следующей разрешенной отправки
   * вызывается только после успешной отправки письма!
   */
  private function setSubmitLock(): void
  {
    $this->ensureSessionStarted();
    $_SESSION[self::SESSION_NEXT_SUBMIT_KEY] = time() + self::SUBMIT_INTERVAL_SECONDS;
  }

  /**
   * отправляет клиенту ответ 429 Too Many Requests
   * с оставшимся до разрешенной отправки временем
   *
   * @param int $RemainingSeconds - оставшееся количество секунд блокировки
   */
  private function outputTooManyRequestsResponse(int $RemainingSeconds): void
  {
    $TimeString = sprintf("%02d:%02d", intdiv($RemainingSeconds, 60), $RemainingSeconds % 60);
    $Message = "Слишком частая отправка заявок."
      . " Повторная отправка будет доступна через " . $TimeString . ".";
    header("Content-Type: application/json; charset=utf-8", true);
    header("Retry-After: " . $RemainingSeconds, false);
    http_response_code(429);
    echo json_encode(
      array(
        "status" => "error",
        "retryAfter" => $RemainingSeconds,
        "message" => $Message,
      ),
      JSON_UNESCAPED_UNICODE
    );
    exit;
  }

  function __construct(Request $Request, TRMDIContainer $DIC)
  {
    parent::__construct($Request, $DIC);
    $this->StatusText = '';

    if (strtolower($this->Request->attributes->get("action")) !== "index") {
      $this->CurrentBasket = new NewBasket($this->_RM);
    }
  }

  /**
   * вызывается при простом обращении к /basket
   * отображает начальную страницу работы с корзиной
   */
  public function actionIndex()
  {
    // ********* форма с товарами !!! ****************
    $this->view = new ArticlesBaseView($this, "basket");

    $Title = \GlobalConfig::$ConfigArray["BasketTitle"];
    $Description = \GlobalConfig::$ConfigArray["CompanyName"] . " - оформление заказа на поставку подвесных потолков и комплектующих";

    $this->setSEO($Title, $Description, \GlobalConfig::$ConfigArray["CommonKeyWords"] . ", заказ товаров");
    $this->view->setMeta("robots", "NOINDEX,NOFOLLOW");
    $this->view->setCanonical($this->buildAbsoluteUrl("/new-basket"));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addCheckoutPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl("/new-basket"),
      "description" => $Description,
    ));
    $this->view->setVar("PageTitle", $Title);
    $this->view->setVarsArray(\GlobalConfig::$ConfigArray);
    $this->view->addCSS(TOPIC . "/css/basket.css", true);
    $this->view->addCSS(TOPIC . "/css/forcatalogpage.css", true);
    // анти-спам: передаем в шаблон состояние ограничения частоты отправки заявок
    $this->view->setVar("NextSubmitTime", $this->getNextSubmitTime());
    $this->view->setVar("SubmitIntervalSeconds", self::SUBMIT_INTERVAL_SECONDS);

    return $this->view->render();
  }

  /**
   * выводит имеющиеся товары в корзине в форму
   */
  public function actionForm()
  {
    if ($this->CurrentBasket->getGoodsFromCookies()) {
      $this->CurrentBasket->initGoodsFromDB();

      $this->view = new CMSBaseView("basketform", null);
      $this->view->setVar("Goods", $this->CurrentBasket->Goods);
      $this->view->setVar("catalogPrefix", \GlobalConfig::$ConfigArray["catalogPrefix"]);
      $this->view->setVar("ImageCatalog", \GlobalConfig::$ConfigArray["ImageCatalog"]);
      return $this->view->render();
    }

    echo self::EmptyBasketText;
    exit;
  }

  /**
   * вычисление и вывод общей стоимости товаров в корзине
   * 
   * @return boolean
   */
  public function actionGetCost()
  {
    if ($this->CurrentBasket->getGoodsFromCookies()) {
      $this->CurrentBasket->initGoodsFromDB();
      $Summ = $this->CurrentBasket->calculateSumm();
      echo $Summ;
    }
    exit;
  }

  /**
   * вызывается при подтверждении отправки формы с корзиной товаров
   * отправляет заказ по eMail
   */
  public function actionConfirm()
  {
    $Message = "";
    $emailaddress = "";
    $this->StatusText = "";

    // анти-спам: ограничение отправки заявок по сессии пользователя
    // не чаще одного раза в SUBMIT_INTERVAL_SECONDS секунд
    $RemainingSeconds = $this->getRemainingSubmitSeconds();
    if ($RemainingSeconds > 0) {
      $this->outputTooManyRequestsResponse($RemainingSeconds);
      return;
    }

    if (!$this->CurrentBasket->getGoodsFromCookies()) {
      $this->StatusText = self::EmptyBasketText;
    }
    try {
      $this->CurrentBasket->initGoodsFromDB();

      for ($i = 0; $i < count($this->CurrentBasket->Goods); $i++) {
        $Message .= ($i + 1)
          . " - [{$this->CurrentBasket->Goods[$i]->Item->getId()}] "
          . "<a href=\""
          . ((strlen($this->Request->server->get("HTTPS")) && $this->Request->server->get("HTTPS", null) != "off") ? "https://" : "http://")
          . $this->Request->getHost() . "/"
          . \GlobalConfig::$ConfigArray["catalogPrefix"] . "/"
          . $this->CurrentBasket->Goods[$i]->Item->getData("table1", "PriceTranslit") . "\">"
          . $this->CurrentBasket->Goods[$i]->Item->getData("table1", "Name")
          . "(" . $this->CurrentBasket->Goods[$i]->Item->getData("vendors", "VendorName") . ")</a> - "
          . $this->CurrentBasket->Goods[$i]->Count . " " . $this->CurrentBasket->Goods[$i]->Item->getData("unit", "UnitShort") . "<br>";
      }

      if (isset(\GlobalConfig::$ConfigArray["PriceCheck"])) {
        $Message .= "На сумму <b>" . $this->CurrentBasket->calculateSumm() . "</b> руб.<br>";
        header("X-PriceCheck: 1");
      }

      $emailaddress = $this->Request->request->get("email");
      $fio = $this->Request->request->get("fio");
      $msg = $this->Request->request->get("message");
      $phone = $this->Request->request->get("phone");

      if (empty($emailaddress)) {
        throw new TRMEMailExceptions("Передан пустой E-mail адрес!");
      }
      if (empty($fio)) {
        $fio = $emailaddress;
      }

      // Все, что приходит из формы, приходит в кодировке DEFAULT_CHARSET = UTF-8,
      // перекодируем в Charset установленный для проекта...
      if (strtolower(\GlobalConfig::$ConfigArray["Charset"]) !== self::DEFAULT_CHARSET) {
        TRMLib::conv($msg, self::DEFAULT_CHARSET, \GlobalConfig::$ConfigArray["Charset"]);
        TRMLib::conv($fio, self::DEFAULT_CHARSET, \GlobalConfig::$ConfigArray["Charset"]);
      }

      $email = new TRMEMail();


      $email->setEmailFrom($emailaddress);
      $email->setNameFrom($fio);

      $email->setConfig(CONFIG . "/emailconfig.php");
      $email->setReplyTo($emailaddress, $fio);

      $email->setMessage($Message);
      $email->addMessage("-----------------------------<br>");
      $email->addMessage($msg);
      $email->addMessage("<br>\nTel: ");
      $email->addMessage($phone);
      $email->addMessage("<br>\nE-mail: ");
      $email->addMessage($emailaddress);
      $email->addMessage("<br>\nName: ");
      $email->addMessage($fio);

      if ($email->sendEmail()) {
        // анти-спам: блокировка устанавливается сразу после успешной отправки письма,
        // до записи заказа в БД, чтобы ошибка БД не позволила отправить письмо повторно
        $this->setSubmitLock();

        $this->StatusText .= "<h3>Заказ успешно отправлен!</h3>"
          . "Через некоторое время вам будет отправлен счет для оплаты на указанный E-mail,"
          . " если возникнут вопросы, с вами свяжутся для уточнения заказа.";

        // если отправлено сообщение, записываем заказ в БД
        $OrderRep = $this->getOrderRepository();
        $ComplexOrder = $OrderRep->getNewObject();
        // $ComplexOrder = new NewComplexOrder();
        $ComplexOrder->setMessage($msg);
        $ComplexOrder->setFIO($fio);
        $ComplexOrder->setEmail($emailaddress);
        $ComplexOrder->setPhone($phone);
        $ComplexOrder->setDateFromTime(time());
        $ComplexOrder->setSessionId(session_id());

        $ComplexOrder->initFromBasket($this->CurrentBasket);


        $OrderRep->insert($ComplexOrder);
        $OrderRep->doInsert();
      } else {
        $this->StatusText = "Ошибка при отправлении заказа!!!";
      }
    } catch (TRMEMailSendingExceptions $e) {
      $this->StatusText = "Ошибка при отправлении заказа!!!<br>";
      $this->StatusText .= " Исключение: " . $e->getMessage();
      error_log("[BASKET_ORDER_ERROR] TRMEMailSendingExceptions: " . $e->getMessage()
        . " | Email: " . $emailaddress . " | Date: " . date('Y-m-d H:i:s'));
    } catch (TRMEMailExceptions $e) {
      $this->StatusText = "Ошибка при отправлении заказа!!!<br>";
      $this->StatusText .= " Исключение: " . $e->getMessage();
      error_log("[BASKET_ORDER_ERROR] TRMEMailExceptions: " . $e->getMessage()
        . " | Email: " . $emailaddress . " | Date: " . date('Y-m-d H:i:s'));
    } catch (Exception $e) {
      $this->StatusText = "Неожиданная ошибка при обработке заказа!";
      error_log("[BASKET_ORDER_ERROR] Unexpected Exception: " . $e->getMessage()
        . " | Trace: " . $e->getTraceAsString());
    }

    //    echo $this->StatusText."<br>".$Message; // TRMLib::conv($Message);
    echo $this->StatusText;
    exit;
  }

  /**
   * освобождаем корзину с товарами
   */
  public function actionEmpty()
  {
    $this->CurrentBasket->emptyBasket();
    echo self::EmptyBasketText;
    exit;
  }
} // NewBasketController

