// call the script
store_referrer();

//read_referrer();

function store_referrer() {

    // get the current and referring pages
    var referrer = document.referrer;
    var current = document.URL;
    var ct = new Date()
    var month = ct.getMonth() + 1;
    currenttime = ct.getFullYear() + "-" + month + "-" + ct.getDate() + " " + ct.getHours() + ":" + ct.getMinutes() + ":" + ct.getSeconds();

    if (referrer == null || referrer == "") {
        referrer = 'Direct Link';
    }   

    // get the current cookies
    var referrer_date_cookie = trimData( readCookie('referrer_date') );
    var referrer_cookie = trimData( readCookie('referrer') );
    var current_cookie = trimData( readCookie('current') );

	 //alert( "referrer_date_cookie" + referrer_date_cookie );
	 //alert( "cookies" + referrer_cookie );
	 //alert( "current_cookie" + current_cookie );
	
    // append the current referrer and current pages to the cookies
    if (referrer_date_cookie == null) {
        referrer_date_cookie = "";
    } else {
        referrer_date_cookie += ';';
    }
    if (referrer_cookie == null) {
        referrer_cookie = "";
    } else {
        referrer_cookie += ';';
    }
    if (current_cookie == null) {
        current_cookie = "";
    } else {
        current_cookie += ';';
    }
    
    referrer_date_cookie += currenttime;
    referrer_cookie += referrer;
    current_cookie += current;

    // store the cookie yum.
    createCookie('referrer_date', referrer_date_cookie, 7);
    createCookie('referrer', referrer_cookie, 7);
    createCookie('current', current_cookie, 7);    
}

// will read and output the referring cookies
// for testing purposes
function read_referrer() {
    // get the current cookies
    var referrer_date_cookie = readCookie('referrer_date');
    var referrer_cookie = readCookie('referrer');
    var current_cookie = readCookie('current');

    alert('Date: ' + referrer_date_cookie + '\n\r' +
            'Referrer: ' + referrer_cookie + '\n\r' +
            'Current: ' + current_cookie);
}


function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else var expires = "";
    document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
}

function readCookie(name) {

var nameEQ = escape(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') { 
			c = c.substring(1, c.length);
		}
        if (c.indexOf(nameEQ) == 0) { 
			return unescape(c.substring(nameEQ.length, c.length));
		}
    }
	
    return null;
}

/* 
	Since version 1.2 
*/ 
function trimData( stringData ){
	
	if (stringData == null ) {
		return null; //return null if it's already null 
	}
	
	var max_data = 14; // the amount of data allowed to be stored
	var data_range = Math.floor( max_data/2 );  // the amount of data to hold in the first set and last set (rounded to lowest whole number) 
	var cdata = stringData.split(';'); // delimiter to split data 
	
	if (cdata.length <=  max_data ) {
		return stringData; // return normal if we have not reached threshold limit 
	}

	var first_set = cdata.slice(0, data_range ); // get the first set 
	var last_set = cdata.slice( (cdata.length - data_range) + 1  ); // get the last set (+1 means we bump off 1 from the beginning of this set, so we can add 1 more later ) 
	
	return first_set.join(";") + ";" +  last_set.join(";");

}

