/******/ (() => { // webpackBootstrap
/*!**************************************!*\
  !*** ./src/assets/public/ff_gmap.js ***!
  \**************************************/
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
jQuery(document).ready(function ($) {
  $(document).on('elementor/popup/show', function (event, id, instance) {
    window.fluentform_gmap_callback();
  });
  window.fluentform_gmap_callback = function () {
    $('.ff_map_autocomplete').each(function (index, item) {
      var $container = $(item);
      var elementId = $container.find("input[data-key_name='address_line_1']").attr('id');
      var input = $container.find('#' + elementId)[0];
      var autoLocateType = typeof $container.data('ff_with_auto_locate') !== 'undefined' ? $container.data('ff_with_auto_locate') : false;
      var options = {
        fields: ["formatted_address", "name", 'address_components', 'geometry', 'icon']
      };
      var autocomplete = new google.maps.places.Autocomplete(input, options);
      $country = $container.find("select[data-key_name='country']");
      if ($country.length) {
        var restrictedCountries = $country.data('autocomplete_restrictions');
        var formattedCountries = [];
        for (var country in restrictedCountries) {
          formattedCountries.push(restrictedCountries[country]);
        }
        if (formattedCountries.length > 0) {
          autocomplete.setComponentRestrictions({
            country: formattedCountries
          });
        }
      }
      if (autoLocateType && autoLocateType != 'no') {
        if (autoLocateType == 'on_load') {
          locateUser(input, $container);
        }
        var bttn = $(input).parent().find('.ff_input-group-append');
        bttn.on('click', function () {
          $(input).val('Please wait ..'); //translate
          locateUser(input, $container);
        });
      }
      autocomplete.addListener("place_changed", function () {
        var place = autocomplete.getPlace();
        place.latLng = place.geometry.location;
        maybeGenerateMap(input, place, $container);
        setAddress(place, $container);
      });
    });
  };
  function setAddress(place, $container) {
    var address = {
      address_line_1: '',
      address_line_2: '',
      city: '',
      state: '',
      zip: '',
      country: ''
    };
    var _iterator = _createForOfIteratorHelper(place.address_components),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var component = _step.value;
        var componentType = component.types[0];
        switch (componentType) {
          case "street_number":
            {
              if (ifAlreadyInPlaceName(place.name, component.long_name)) {
                break;
              }
              address.address_line_1 = "".concat(component.long_name, " ").concat(address.address_line_1).trim();
              break;
            }
          case "route":
            {
              if (ifAlreadyInPlaceName(place.name, component.short_name)) {
                break;
              }
              if (address.address_line_1) {
                address.address_line_1 += " " + component.short_name;
              } else {
                address.address_line_1 = component.short_name;
              }
              break;
            }
          case "postal_code":
            {
              address.zip = "".concat(component.long_name).concat(address.zip);
              break;
            }
          case "postal_code_suffix":
            {
              address.zip = "".concat(address.zip, "-").concat(component.long_name);
              break;
            }
          case "locality":
          case "postal_town":
            address.city = component.long_name;
            break;
          case "administrative_area_level_1":
            if (!address.state && !address.country) {
              address.state = component.long_name;
            } else if (!address.state && address.country) {
              // Likely a country in this case, skip assigning to state
            }
            break;
          case "administrative_area_level_2":
            if (!address.state && address.country) {
              address.state = component.long_name;
            }
            break;
          case "administrative_area_level_3":
          case "administrative_area_level_4":
            if (address.address_line_2) {
              address.address_line_2 = " " + component.short_name;
            } else {
              address.address_line_2 = component.short_name;
            }
          case "country":
            address.country = component.short_name;
            break;
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
    if (!address.address_line_1) {
      address.address_line_1 = place.name;
    }
    if (place.latLng) {
      var $form = $container.closest('form');
      $form.find("input[data-key_name='latitude']").val(place.latLng.lat);
      $form.find("input[data-key_name='longitude']").val(place.latLng.lng);
    }
    if (place.name != address.address_line_1 && typeof place.name != "undefined") {
      if ($container.find("input[data-key_name='address_line_2']").length) {
        address.address_line_2 = address.address_line_1;
        address.address_line_1 = place.name;
      } else {
        address.address_line_1 = place.name + " " + address.address_line_1;
      }
    }
    $container.find(':input').val('').trigger('change');
    $.each(address, function (key, value) {
      if (value) {
        if (key == 'country') {
          $container.find("select[data-key_name='" + key + "']").val(value).trigger('change');
        } else {
          $container.find("input[data-key_name='" + key + "']").val(value).trigger('change');
        }
      }
    });
  }
  function maybeGenerateMap(input, place, $container) {
    var showMapEnabled = typeof $container.data('ff_with_g_map') !== 'undefined';
    if (!showMapEnabled) {
      return;
    }
    var isDragable = true; //another option or maybe a filter
    var $addressElm = $(input).closest('.ff_map_autocomplete');
    $mapDom = $addressElm.find('.ff_g_map');
    if (!$mapDom.length) {
      $('<div/>', {
        "class": 'ff_g_map',
        id: 'ff_map_elm_' + $(input).attr('id'),
        style: 'height:300px'
      }).appendTo($addressElm);
      $mapDom = $addressElm.find('.ff_g_map');
    }
    if (document.getElementById($mapDom.attr('id'))) {
      var map = new google.maps.Map(document.getElementById($mapDom.attr('id')), {
        center: {
          lat: 50.064192,
          lng: -130.605469
        },
        //add filter maybe
        zoom: 3
      });
      var marker = new google.maps.Marker({
        map: map,
        draggable: isDragable,
        anchorPoint: new google.maps.Point(0, -29)
      });
      marker.setVisible(false);
      if (!place.geometry || !place.geometry.location) {
        return;
      }
      google.maps.event.addListener(marker, "dragend", function (event) {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
          'latLng': event.latLng
        }, function (places, status) {
          if (status == google.maps.GeocoderStatus.OK && places[0]) {
            places[0].latLng = event.latLng;
            setAddress(places[0], $container);
          }
        });
      });
      if (place.geometry.viewport) {
        map.fitBounds(place.geometry.viewport);
      } else {
        map.setCenter(place.geometry.location);
        map.setZoom(17);
      }
      marker.setPosition(place.geometry.location);
      marker.setVisible(true);
    }
  }
  function locateUser(input, $container) {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        var latlng = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
          'latLng': latlng
        }, function (places, status) {
          if (status == google.maps.GeocoderStatus.OK && places[0]) {
            places[0].latLng = latlng;
            setAddress(places[0], $container);
            maybeGenerateMap(input, places[0], $container);
          }
        });
      }, function () {
        fetchLocationByIP().then(function (ipLocation) {
          if (ipLocation) {
            processLocation(input, ipLocation, $container);
          } else {
            $(input).val('');
          }
        });
      });
    } else {
      fetchLocationByIP().then(function (ipLocation) {
        if (ipLocation) {
          processLocation(input, ipLocation, $container);
        } else {
          $(input).val('');
        }
      });
    }
  }
  function fetchLocationByIP() {
    var key = window.ff_gmap_vars.api_key;
    return fetch('https://www.googleapis.com/geolocation/v1/geolocate?key=' + key, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    }).then(function (response) {
      console.log(response);
      if (!response.ok) {
        throw new Error('Google IP Geolocation failed');
      }
      return response.json();
    }).then(function (data) {
      return {
        lat: data.location.lat,
        lng: data.location.lng,
        accuracy: data.accuracy
      };
    })["catch"](function (error) {
      console.error('Error fetching IP location:', error);
      return null;
    });
  }
  function processLocation(input, latlng, $container) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'latLng': latlng
    }, function (places, status) {
      if (status == google.maps.GeocoderStatus.OK && places[0]) {
        places[0].latLng = latlng;
        setAddress(places[0], $container);
        maybeGenerateMap(input, places[0], $container);
      }
    });
  }
  function ifAlreadyInPlaceName(placeName, val) {
    return placeName && placeName.includes(val);
  }
}(jQuery));
/******/ })()
;