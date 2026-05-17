"use strict";

function FeaturesSelectorConfig(StartGroupId, PricePrefix, StartGroupTranslit) {
  return {
    CloseClassName: "CloseButton",
    OpenClassName: "OpenButton",
    GetFeaturesURL: "/Ajax/get-features-list",
    DivNameId: "FeaturesSelector",
    IdFieldName: "ID_group",
    AllFeatures: [],
    Selected: [],
    StartGroupId: StartGroupId,
    PricePrefix: PricePrefix,
    StartGroupTranslit: StartGroupTranslit,
    addToSelected: function (id, value) {
      for (var i = 0; i < this.Selected.length; i++) {
        // если такое значение для характеристики с id уже есть,
        // то прекращаем выполнение
        if (this.Selected[i].id.toString() === id.toString()
          &&
          this.Selected[i].value.toString() === value.toString()) {
          return;
        }
      }
      this.Selected.push({
        id: id,
        value: value
      });
    },
    removeFromSelected: function (id, value) {
      for (var i = 0; i < this.Selected.length; i++) {
        // если такое значение для характеристики с id есть в масииве,
        // то вырезаем и прекращаем выполнение
        if (this.Selected[i].id.toString() === id.toString()
          &&
          this.Selected[i].value.toString() === value.toString()) {
          this.Selected.splice(i, 1);
          return;
        }
      }
    },
    getTranslitForId: function (id) {
      for (var i = 0; i < this.AllFeatures.length; i++) {
        if (this.AllFeatures[i].ID_Feature.toString() === id.toString()) {
          return this.AllFeatures[i].FeaturesTranslit;
        }
      }
      return null;
    },
    generateSelectedFiltersString: function (LocalSelectorConfig) {
      var CurrentUrl = "";
      var CurrentName = "";
      var FirstFlag = true;
      var OldName = "";

      for (var i = 0; i < LocalSelectorConfig.Selected.length || 0; i++) {
        CurrentName = LocalSelectorConfig.getTranslitForId(LocalSelectorConfig.Selected[i].id);
        if (FirstFlag) {
          CurrentUrl = CurrentName + "-eqv-" + encodeURIComponent(LocalSelectorConfig.Selected[i].value);
          FirstFlag = false;
        } else if (CurrentName !== OldName) {
          CurrentUrl += "|" + CurrentName + "-eqv-" + encodeURIComponent(LocalSelectorConfig.Selected[i].value);
        } else {
          CurrentUrl += "-or-" + encodeURIComponent(LocalSelectorConfig.Selected[i].value);
        }
        OldName = CurrentName;
      }

      return CurrentUrl;
    },
  };
}

function FeaturesSelector(LocalSelectorConfig, onChangeCallback) {
  var instance = this;

  this.JSONfinal = function (str, StatusCode, StatusText) {
    if (!checkAndAlertStatus(StatusCode, StatusText)) { return; }
    var JSONMenu = JSON.parse(str);

    var UlNode = instance.generateUlNew(JSONMenu);
    if (!UlNode) { return; }
    var AJAXDiv = document.createElement('div');

    //addClass(AJAXDiv, "SelectorTreeDiv");
    AJAXDiv.appendChild(UlNode);

    var ResetApplyButtonsWrapper = document.createElement('div');
    ResetApplyButtonsWrapper.classList.add("SelectorResetApplyButtonsWrapper");

    var ResetButton = document.createElement('button');
    ResetButton.appendChild(document.createTextNode("Сбросить"));
    ResetButton.classList.add("SelectorResetButton");
    ResetButton.onclick = instance.resetSelector.bind(instance);

    var ApplyButton = document.createElement('button');
    ApplyButton.appendChild(document.createTextNode("Выбрать"));
    ApplyButton.classList.add("SelectorApplyButton");
    ApplyButton.onclick = instance.sendSelectorRequest.bind(instance);

    ResetApplyButtonsWrapper.appendChild(ResetButton);
    ResetApplyButtonsWrapper.appendChild(ApplyButton);
    AJAXDiv.appendChild(ResetApplyButtonsWrapper);

    var ContainerDiv = document.getElementById(LocalSelectorConfig.DivNameId);
    ContainerDiv.appendChild(AJAXDiv);
    ContainerDiv.style.display = "block";
    //        ContainerDiv.innerHTML = "";
  };

  this.fetchJSONTree = function (callbackFunctions) {
    var callbackFunction = function (str, StatusCode, StatusText) {
      if (!callbackFunctions) {
        return;
      }
      for (var i = 0; i < callbackFunctions.length; i++) {
        callbackFunctions[i](str, StatusCode, StatusText, LocalSelectorConfig);
      }
      // callbackFunctions.forEach((fn) => { fn(str, StatusCode, StatusText); });
    };

    var u = new URL(document.location.href);
    var filters = u.searchParams.get("filters");

    sendRequest(LocalSelectorConfig.GetFeaturesURL,
      "POST",
      JSON.stringify({
        GroupId: LocalSelectorConfig.StartGroupId,
        URL: filters
      }),
      callbackFunction,
      this);
  };

  this.clickButton = function (e) {
    // не можем применять LocalSelectorConfig.CloseClassName, так как во время нажатия 
    // контекст будет совсем другой
    //        switchClass(e.target, LocalSelectorConfig.CloseClassName, LocalSelectorConfig.OpenClassName);
    var resizerBtn = e.currentTarget.querySelector('button');
    if (!resizerBtn) { return false; }
    resize(resizerBtn, LocalSelectorConfig.CloseClassName, LocalSelectorConfig.OpenClassName);
  };

  this.changeCheck = function (e) {
    if (e.target.checked) {
      LocalSelectorConfig.addToSelected(
        e.target.parentNode.parentNode.parentNode.parentNode.id,
        e.target.value
      );
    }
    else {
      LocalSelectorConfig.removeFromSelected(
        e.target.parentNode.parentNode.parentNode.parentNode.id,
        e.target.value
      );
    }
  };

  this.generateUlNew = function (Nodes) {
    var TreeUl;
    var LiNode;
    var SelectorLiWrapperDiv;
    var SpanNode;
    var ButtonDiv;
    var UlNode;
    var FeatureText;

    var CheckFlag;

    LocalSelectorConfig.AllFeatures = Nodes[0];
    if (!LocalSelectorConfig.AllFeatures.length) {
      document.getElementById(LocalSelectorConfig.DivNameId).innerHTML = "";
      document.getElementById(LocalSelectorConfig.DivNameId).style.display = "none";
      return null;
    }
    LocalSelectorConfig.Selected = Nodes[1];

    TreeUl = document.createElement('ul');
    for (var i = 0; i < LocalSelectorConfig.AllFeatures.length; i++) {
      LiNode = document.createElement('li');
      LiNode.id = LocalSelectorConfig.AllFeatures[i].ID_Feature;

      ButtonDiv = document.createElement('button');
      ButtonDiv.setAttribute("type", "button");
      ButtonDiv.setAttribute("aria-label", "Показать/скрыть значения характеристики ");
      ButtonDiv.classList.toggle(LocalSelectorConfig.CloseClassName);

      SpanNode = document.createElement('span');
      FeatureText = LocalSelectorConfig.AllFeatures[i].FeatureTitle;
      if (LocalSelectorConfig.AllFeatures[i].Reserv.length) {
        FeatureText += ", " + LocalSelectorConfig.AllFeatures[i].Reserv;
      }
      SpanNode.innerHTML = FeatureText;

      SelectorLiWrapperDiv = document.createElement('div');
      SelectorLiWrapperDiv.classList.add("SelectorLiWrapperDiv");
      SelectorLiWrapperDiv.onclick = this.clickButton;

      SelectorLiWrapperDiv.appendChild(ButtonDiv);
      SelectorLiWrapperDiv.appendChild(SpanNode);

      UlNode = document.createElement('ul');
      UlNode.style.height = 0;
      UlNode.style.overflow = "hidden";

      CheckFlag = false;

      for (var k = 0; k < LocalSelectorConfig.AllFeatures[i].Values.length; k++) {
        var NewLiNode;
        var LabelNode;
        var CheckNode;
        var TextNode;

        NewLiNode = document.createElement('li');
        UlNode.appendChild(NewLiNode);

        LabelNode = document.createElement('label');
        NewLiNode.appendChild(LabelNode);

        CheckNode = document.createElement('input');
        LabelNode.appendChild(CheckNode);

        TextNode = document.createTextNode(LocalSelectorConfig.AllFeatures[i].Values[k].FeaturesValue);
        LabelNode.appendChild(TextNode);

        CheckNode.type = "checkbox";
        CheckNode.name = "featurescheck[]";
        CheckNode.value = LocalSelectorConfig.AllFeatures[i].Values[k].FeaturesValue;
        CheckNode.onchange = function (e) {
          this.changeCheck(e);
          if (onChangeCallback && (typeof onChangeCallback === "function")) {
            onChangeCallback(e, LocalSelectorConfig);
          }
        }.bind(this);

        for (var cnt = 0; cnt < LocalSelectorConfig.Selected.length; cnt++) {
          if (LocalSelectorConfig.AllFeatures[i].Values[k].ID_Feature === LocalSelectorConfig.Selected[cnt].id
            &&
            LocalSelectorConfig.AllFeatures[i].Values[k].FeaturesValue === LocalSelectorConfig.Selected[cnt].value
          ) {
            CheckNode.checked = true;
            CheckFlag = true;
          }
        }
      }

      if (CheckFlag) {
        UlNode.style.height = "auto";
        //switchClass(ButtonDiv, LocalSelectorConfig.OpenClassName, LocalSelectorConfig.CloseClassName);
        ButtonDiv.classList.toggle(LocalSelectorConfig.CloseClassName);
        ButtonDiv.classList.toggle(LocalSelectorConfig.OpenClassName);
      }

      LiNode.appendChild(SelectorLiWrapperDiv);
      LiNode.appendChild(UlNode);

      TreeUl.appendChild(LiNode);
    }
    return TreeUl;
  };

  this.sendSelectorRequest = function () {
    var Filters = LocalSelectorConfig.generateSelectedFiltersString(LocalSelectorConfig);

    var CurrentUrl = new URL(document.location);

    var NewUrl = CurrentUrl;
    
    if (NewUrl.pathname.indexOf('/' + LocalSelectorConfig.PricePrefix + '/') === -1) {
      NewUrl.pathname = '/' + LocalSelectorConfig.PricePrefix + '/' + LocalSelectorConfig.StartGroupTranslit;
    }

    if (Filters.length) {
      NewUrl.searchParams.set("filters", Filters);
    } else {
      NewUrl.searchParams.delete("filters");
    }
    NewUrl.searchParams.delete("page");

    window.location = NewUrl;
  };

  this.resetSelector = function () {
    LocalSelectorConfig.Selected = [];
    this.sendSelectorRequest();
  };
}
