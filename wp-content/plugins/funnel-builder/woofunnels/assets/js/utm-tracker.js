/*global wffnUtm */
var wffnUtm_terms = wffnUtm.cookieKeys, wffnCookieManage = {
    setCookie: function (e, o, t) {
        var r = new Date();
        r.setTime(r.getTime() + 24 * t * 60 * 60 * 1e3);
        var c = "expires=" + r.toUTCString();
        var basehost = ';domain=.' + wffnGetHost(document.location.hostname);

        document.cookie = e + "=" + o + ";" + c + basehost + ";path=/";
    }, getCookie: function (e) {
        for (var o = e + "=", t = document.cookie.split(";"), r = 0; r < t.length; r++) {
            for (var c = t[r]; " " == c.charAt(0);) c = c.substring(1);
            if (0 == c.indexOf(o)) return c.substring(o.length, c.length);
        }
        return "";
    }, remove: function (e) {
        var o = new Date();
        o.setTime(o.getTime() - 864e5);
        var t = "expires=" + o.toUTCString();
        document.cookie = e + "=;" + t + ";path=/";
    }, commons: {
        inArray: function (e, o) {
            return -1 === o.indexOf(e);
        }
    }
};


function wffnGetHost(url) {
    var o = {
            strictMode: false,
            key: ['source', 'protocol', 'authority', 'userInfo', 'user', 'password', 'host', 'port', 'relative', 'path', 'directory', 'file', 'query', 'anchor'],
            q: {
                name: 'queryKey',
                parser: /(?:^|&)([^&=]*)=?([^&]*)/g
            },
            parser: {
                strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
                loose: /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
            }
        },
        m = o.parser[o.strictMode ? 'strict' : 'loose'].exec(url),
        uri = {},
        i = 14;

    while (i--) {
        uri[o.key[i]] = m[i] || '';
    }

    uri[o.q.name] = {};
    uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
        if ($1) {
            uri[o.q.name][$1] = $2;
        }
    });

    return uri.host.replace('www.', '');
}

function wffnGetQueryVars() {

    try {

        var result = {}, tmp = [];

        window.location.search
            .substr(1)
            .split("&")
            .forEach(function (item) {

                tmp = item.split('=');

                if (tmp.length > 1) {
                    result[tmp[0]] = tmp[1];
                }

            });

        return wffnDefaultEvent(result);

    } catch (e) {
        console.log(e);
    }

}

/** Add default parameter utm event **/
function wffnDefaultEvent(result) {
    if (typeof Intl === "object" && typeof Intl.DateTimeFormat() === "object") {
        let resolved = Intl.DateTimeFormat().resolvedOptions();
        if (resolved.hasOwnProperty('timeZone')) {
            result.timezone = resolved.timeZone;
        }
    }

    result.flt = wffnGetAdminTime();

    /**
     * save referrer when manually pass by url
     */
    if (result.hasOwnProperty('fkreferrer') && result.fkreferrer !== '') {
        result.referrer = result.fkreferrer;
        delete result.fkreferrer;
    } else {
        const WffnfkRef = document.referrer;
        const getDomain = url => new URL(url).hostname;

        result.referrer = (WffnfkRef && !wffnUtm.excludeDomain.some(domain => getDomain(WffnfkRef).endsWith(domain)) && !WffnfkRef.includes(window.location.hostname)) ? WffnfkRef : '';

    }

    result.fl_url = (typeof window.location.pathname !== "undefined") ? window.location.pathname : '/';

    let getDevice = wffnDetectDevice();
    if (typeof getDevice !== "undefined" && getDevice !== "") {
        if (typeof getDevice.browser.name !== "undefined") {
            result.browser = getDevice.browser.name;
        }
        if (typeof getDevice.is_mobile !== "undefined") {
            result.is_mobile = getDevice.is_mobile;
        }
    }
    return result;
}


/** get wp admin current time*/
function wffnGetAdminTime(getEpochTime = false, isObject = false) {
    var getTime = new Date();
    var getIsoString = getTime.toISOString();

    // Convert the ISO string to a Date object
    var dateFromIso = new Date(getIsoString);

    // Set Admin offset to get user time according admin
    dateFromIso.setMinutes(dateFromIso.getMinutes() + parseInt(wffnUtm.utc_offset));
    getIsoString = dateFromIso.toISOString();
    const [datePart, timePart] = getIsoString.split("T");

    // Extract year, month, day
    const [getYear, getMonth, getDay] = datePart.split("-").map(Number);
    // Extract hours, minutes, seconds
    const [getHours, getMinutes, secondsWithMillis] = timePart.split(":");
    const getSeconds = secondsWithMillis.split(".")[0];
    if (true === getEpochTime) {
        /** get time in seconds **/
        dateFromIso = new Date(getYear + '-' + (getMonth) + '-' + getDay + ' ' + getHours + ':' + getMinutes + ':' + getSeconds);
        return Math.round(dateFromIso.getTime() / 1000);
    }

    if (true === isObject) {
        return new Date(getYear + '-' + (getMonth) + '-' + getDay + ' ' + getHours + ':' + getMinutes + ':' + getSeconds);

    }

    return getYear + '-' + (getMonth) + '-' + getDay + ' ' + getHours + ':' + getMinutes + ':' + getSeconds;
}

function wffnGetTrafficSource() {
    try {

        var referrer = document.referrer.toString();

        var direct = referrer.length === 0;
        //noinspection JSUnresolvedVariable
        var internal = direct ? false : referrer.indexOf(wffnUtm.site_url) === 0;
        var external = !(direct || internal);
        var cookie = wffnCookieManage.getCookie('wffn_traffic_source') === '' ? false : wffnCookieManage.getCookie('wffn_traffic_source');

        if (external === false) {
            return cookie ? cookie : 'direct';
        } else {
            return cookie && cookie === referrer ? cookie : referrer;
        }

    } catch (e) {

        return '';

    }


}

function wffnManageCookies() {


    try {
        var source = wffnGetTrafficSource();
        if (source !== 'direct') {
            wffnCookieManage.setCookie('wffn_traffic_source', source, 2);
        } else {
            wffnCookieManage.remove('wffn_traffic_source');
        }

        var queryVars = wffnGetQueryVars();


        for (var k in wffnUtm_terms) {
            if (Object.prototype.hasOwnProperty.call(queryVars, wffnUtm_terms[k])) {
                /**
                 * restricted override cookies for user journey
                 */
                if (['flt', 'fl_url', 'referrer'].indexOf(wffnUtm_terms[k]) !== -1) {
                    if ('undefined' !== typeof wffnCookieManage && '' === wffnCookieManage.getCookie('wffn_' + wffnUtm_terms[k])) {
                        wffnCookieManage.setCookie('wffn_' + wffnUtm_terms[k], queryVars[wffnUtm_terms[k]], 2);
                    }
                } else {
                    wffnCookieManage.setCookie('wffn_' + wffnUtm_terms[k], queryVars[wffnUtm_terms[k]], 2);
                }
            }
        }

    } catch (e) {
        console.log(e);
    }


}

/**
 * Return UTM terms from request query variables or from cookies.
 */
function wffnGetUTMs() {
    try {
        var terms = {};
        var queryVars = wffnGetQueryVars();
        /** exclude parameter for utm event **/
        var excludeArray = ["flt", "timezone", "is_mobile", "browser", "fbclid", "gclid", "referrer", "fl_url"];
        for (var k in wffnUtm_terms) {

            if (wffnCookieManage.getCookie('wffn_' + wffnUtm_terms[k]) === '' && Object.prototype.hasOwnProperty.call(queryVars, wffnUtm_terms[k])) {
                terms[wffnUtm_terms[k]] = wffnCookieManage.getCookie('wffn_' + wffnUtm_terms[k]);

            }
        }
        return terms;

    } catch (e) {
        return {};
    }

}

/* eslint-disable no-unused-vars */
function wffnAddTrafficParamsToEvent(params) {

    try {
        var get_generic_params = wffnUtm.genericParamEvents;
        var json_get_generic_params = JSON.parse(get_generic_params);

        for (var k in json_get_generic_params) {
            params[k] = json_get_generic_params[k];
        }


        /**
         * getting current day and time to send with this event
         */
        var e = wffnGetAdminTime(false, true);
        var a = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"][e.getDay()],
            b = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"][e.getMonth()],
            c = ["00-01", "01-02", "02-03", "03-04", "04-05", "05-06", "06-07", "07-08", "08-09", "09-10", "10-11", "11-12", "12-13", "13-14", "14-15", "15-16", "16-17", "17-18", "18-19", "19-20", "20-21", "21-22", "22-23", "23-24"][e.getHours()];

        params.event_month = b;
        params.event_day = a;
        params.event_hour = c;

        params.traffic_source = wffnGetTrafficSource();

        var getUTMs = wffnGetUTMs();

        for (var ki in getUTMs) {

            params[ki] = getUTMs[ki];

        }
        return params;

    } catch (eeX) {

        return params;

    }
}

/** return device and browser info **/
function wffnDetectDevice() {
    let header = [navigator.userAgent, navigator.vendor, window.opera];
    let is_mobile = false;
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        is_mobile = true;
    }

    /** check device for facebook application **/
    if (false === is_mobile) {
        let fbAgent = navigator.userAgent || navigator.vendor || window.opera;
        if ((fbAgent.indexOf("FBAN") > -1) || (fbAgent.indexOf("FBAV") > -1)) {
            is_mobile = true;
        }
    }

    let databrowser = [
        {name: 'Chrome', value: 'Chrome', version: 'Chrome'},
        {name: 'Firefox', value: 'Firefox', version: 'Firefox'},
        {name: 'Safari', value: 'Safari', version: 'Version'},
        {name: 'Internet Explorer', value: 'MSIE', version: 'MSIE'},
        {name: 'Opera', value: 'Opera', version: 'Opera'},
        {name: 'BlackBerry', value: 'CLDC', version: 'CLDC'},
        {name: 'Mozilla', value: 'Mozilla', version: 'Mozilla'}
    ];
    var agent = header.join(' '),
        browser = wffnDetectBrowser(agent, databrowser);
    return {is_mobile: is_mobile, browser: browser};
}

function wffnDetectBrowser(string, data) {
    var i = 0,
        j = 0,
        regex,
        regexv,
        match,
        matches,
        version;

    for (i = 0; i < data.length; i += 1) {
        regex = new RegExp(data[i].value, 'i');
        match = regex.test(string);
        if (match) {
            regexv = new RegExp(data[i].version + '[- /:;]([\\d._]+)', 'i');
            matches = string.match(regexv);
            version = '';
            if (matches) {
                if (matches[1]) {
                    matches = matches[1];
                }
            }
            if (matches) {
                matches = matches.split(/[._]+/);
                for (j = 0; j < matches.length; j += 1) {
                    if (j === 0) {
                        version += matches[j] + '.';
                    } else {
                        version += matches[j];
                    }
                }
            } else {
                version = '0';
            }
            return {
                name: data[i].name,
                version: parseFloat(version)
            };
        }
    }
    return {name: 'unknown', version: 0};
}

wffnManageCookies();
