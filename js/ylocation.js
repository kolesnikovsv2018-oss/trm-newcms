"use strict";

function ylocation(DivName)
{

if( undefined === DivName ) { DivName = "YLocation"; }
this.Country = null;
this.Province = null;
this.Area = null;
this.Locality = null;

this.CurrentLocation = null;

this.start = function()
{
    var context = this;

    if (typeof ymaps === 'undefined') {
        if (typeof NewCMSErrorHandler !== 'undefined') {
            NewCMSErrorHandler.warn('ylocation: Яндекс.Карты не загружены');
        }
        return;
    }

    ymaps.ready( function() {

        ymaps.geolocation.get({
            provider: 'yandex',
            autoReverseGeocode: true
        })
        .then(function (result) {
            try {
                if (!result || !result.geoObjects || result.geoObjects.length === 0) {
                    if (typeof NewCMSErrorHandler !== 'undefined') {
                        NewCMSErrorHandler.warn('ylocation: пустой ответ API');
                    }
                    return;
                }
                var geoObj = result.geoObjects.get(0);
                if (!geoObj) { return; }
                var metaData = geoObj.properties.get('metaDataProperty');
                if (!metaData || !metaData.GeocoderMetaData || !metaData.GeocoderMetaData.Address) {
                    if (typeof NewCMSErrorHandler !== 'undefined') {
                        NewCMSErrorHandler.warn('ylocation: нет данных адреса в ответе');
                    }
                    return;
                }
                var Arr = metaData.GeocoderMetaData.Address.Components;
                if (!Array.isArray(Arr) || !Arr.length) { return; }

                Arr.forEach( function(Item){
                    if (!Item || !Item.kind || !Item.name) { return; }
                    if( Item.kind === "country" ) { context.Country = Item.name; }
                    if( Item.kind === "province" ) { context.Province = Item.name; }
                    if( Item.kind === "area" ) { context.Area = Item.name; }
                    if( Item.kind === "locality" ) { context.Locality = Item.name; }
                });

                if(context.Locality) { context.CurrentLocation = context.Locality; }
                else if(context.Area) { context.CurrentLocation = context.Area; }
                else if(context.Province) { context.CurrentLocation = context.Province; }
                else if(context.Country) { context.CurrentLocation = context.Country; }
                else { return; }

                var Div = document.getElementById(DivName);
                if( Div ) { Div.innerHTML = context.CurrentLocation; }
            } catch (e) {
                if (typeof NewCMSErrorHandler !== 'undefined') {
                    NewCMSErrorHandler.error('ylocation: исключение при обработке ответа', { message: e.message });
                }
            }
        })
        .catch(function (err) {
            if (typeof NewCMSErrorHandler !== 'undefined') {
                NewCMSErrorHandler.error('ylocation: ошибка API Яндекс.Карт', { message: String(err) });
            }
        });
    });
};

}