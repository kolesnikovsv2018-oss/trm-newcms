"use strict";

function TreeConfig2(StartGroupId, ActiveGroupId, ActiveElem, LinkPrefix) {
  return {
    StartGroupId: StartGroupId,
    ActiveGroupId: ActiveGroupId,
    LinkPrefix: LinkPrefix,
    ActiveElem: ActiveElem,
    MultipleClassName: "close",
    SingleClassName: "single",
    GetTreeURL: "/Ajax/get-group-tree",
    DivNameId: "newmenu",
    SubGroupsDivId: "SubGroupsDiv",
    IdFieldName: "ID_group",
    OnClickFunction: null,
    JSONMenu: [],
    getSubGroups: function (ActiveGroup, JSONMenu) {
      if (JSONMenu === undefined) { JSONMenu = this.JSONMenu; }

      // если активная группа - корневая
      // подразумевается, что в корне все группы имеют одинакового родителя
      if (JSONMenu[0].GroupID_parent.toString() === ActiveGroup.toString()) {
        return JSONMenu;
      }

      for (var i = 0; i < JSONMenu.length; i++) {
        if (JSONMenu[i].children !== undefined && JSONMenu[i].children.length) {
          if (JSONMenu[i].ID_group.toString() === ActiveGroup.toString()) {
            return JSONMenu[i].children;
          }
          else {
            var SubGroups = this.getSubGroups(ActiveGroup, JSONMenu[i].children);
            if (SubGroups) {
              return SubGroups;
            }
          }
        }
      }
      return null;
    }
  };
}

function CatalogTree2(LocalMenuConfig) {
  var instance = this;

  this.setMainMenu = function (str, StatusCode, StatusText) {
    if (!checkAndAlertStatus(StatusCode, StatusText)) { return; }

    var JSONMenu = parseJSONSafe(str, null);
    if (!JSONMenu || !Array.isArray(JSONMenu)) {
      if (typeof NewCMSErrorHandler !== 'undefined') {
        NewCMSErrorHandler.error('CatalogTree2.setMainMenu: пустой или невалидный ответ', { preview: (str || '').substring(0, 80) });
      }
      return;
    }

    LocalMenuConfig.JSONMenu = JSONMenu;
    if (!LocalMenuConfig.ActiveElem) {
      if (typeof NewCMSErrorHandler !== 'undefined') {
        NewCMSErrorHandler.warn('CatalogTree2.setMainMenu: ActiveElem не задан');
      }
      return;
    }

    var ul = instance.generateUlNew(LocalMenuConfig.JSONMenu);
    if (ul) {
      LocalMenuConfig.ActiveElem.appendChild(ul);
    }
  };

  this.setSubGroups = function (str, StatusCode, StatusText) {
    if (!checkAndAlertStatus(StatusCode, StatusText)) { return; }

    var JSONMenu = parseJSONSafe(str, null);
    if (!JSONMenu) {
      if (typeof NewCMSErrorHandler !== 'undefined') {
        NewCMSErrorHandler.error('CatalogTree2.setSubGroups: пустой или невалидный ответ', { preview: (str || '').substring(0, 80) });
      }
      return;
    }

    LocalMenuConfig.JSONMenu = JSONMenu;

    var SubGroups = LocalMenuConfig.getSubGroups(LocalMenuConfig.ActiveGroupId);
    var UlNode = instance.generateSubGroupsUl(SubGroups);
    if (UlNode) {
      var MenuTreeDiv = document.createElement('div');
      MenuTreeDiv.appendChild(UlNode);
      MenuTreeDiv.style.display = "block";
      var ContainerDiv = document.getElementById(LocalMenuConfig.SubGroupsDivId);
      if (ContainerDiv) {
        ContainerDiv.appendChild(MenuTreeDiv);
        ContainerDiv.style.display = "block";
      } else if (typeof NewCMSErrorHandler !== 'undefined') {
        NewCMSErrorHandler.warn('CatalogTree2.setSubGroups: контейнер не найден', { id: LocalMenuConfig.SubGroupsDivId });
      }
    }
  };

  this.fetchJSONTree = function (callbackFunctions) {
    var callbackFunction = function (str, StatusCode, StatusText) {
      if (!callbackFunctions) {
        return;
      }
      for (var i = 0; i < callbackFunctions.length; i++) {
        callbackFunctions[i](str, StatusCode, StatusText);
      }
      // callbackFunctions.forEach((fn) => { fn(str, StatusCode, StatusText); });
    };

    sendRequest(LocalMenuConfig.GetTreeURL,
      "POST",
      JSON.stringify({ Present: 1, ID: LocalMenuConfig.StartGroupId }),
      callbackFunction.bind(this),
      this);
  };

  this.generateSubGroupsUl = function (SubGroups) {
    if (!SubGroups || !SubGroups.length) {
      return null;
    }
    var UlNode = document.createElement('ul');
    SubGroups.forEach(function (item) {
      var LiNode = document.createElement('li');
      var AEl = document.createElement('a');
      AEl.href = "/" + LocalMenuConfig.LinkPrefix + "/" + item.GroupTranslit;
      AEl.text = item.GroupTitle;

      LiNode.appendChild(AEl);
      UlNode.appendChild(LiNode);

    });
    return UlNode;
  };

  this.generateUlNew = function (Nodes) {
    var TreeUl = document.createElement('ul');
    var LiNode;
    var DivEl;
    var AEl;
    var WrapperDiv;

    for (var i = 0; i < Nodes.length; i++) {
      LiNode = document.createElement('li');
      LiNode.id = Nodes[i][LocalMenuConfig.IdFieldName];

      // для иконки раскрытия/скрытия подгрупп
      DivEl = document.createElement('div');

      AEl = document.createElement('a');
      AEl.href = "/" + LocalMenuConfig.LinkPrefix + "/" + Nodes[i].GroupTranslit;
      AEl.text = Nodes[i].GroupTitle;

      WrapperDiv = document.createElement('div');
      WrapperDiv.appendChild(DivEl);
      WrapperDiv.appendChild(AEl);

      LiNode.appendChild(WrapperDiv);

      if (typeof (Nodes[i].children) !== 'undefined' && Nodes[i].children) {
        if (LocalMenuConfig.OnClickFunction) {
          // добавляем отклик на нажатие-раскрытие элемента
          DivEl.onclick = LocalMenuConfig.OnClickFunction;
        }
        WrapperDiv.classList.add('close');
        // DivEl.className = "close";
        LiNode.appendChild(this.generateUlNew(Nodes[i].children));
        LiNode.className = LocalMenuConfig.MultipleClassName;
      }
      else {
        //var Href = AEl.href;
        WrapperDiv.classList.add('single');
        // DivEl.className = "single";
        //DivEl.onclick = function(){ window.location = Href; };
        LiNode.className = LocalMenuConfig.SingleClassName;
      }

      TreeUl.appendChild(LiNode);
    }

    return TreeUl;
  };
}
