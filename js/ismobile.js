/**
 * Проверяет, является ли устройство мобильным.
 * @returns {boolean}
 */
function isMobile() {
  return (
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    || (
      /Macintosh/i.test(navigator.userAgent)
      && navigator.maxTouchPoints > 1
    )
  );
}