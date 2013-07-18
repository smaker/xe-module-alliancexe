function doChangeSite() {
    var site_srl = jQuery('.siteSelector option:selected').val();
    location.href = decodeURI(current_url).setQuery('cur_site',site_srl);
}

function completeAxeAuth(ret_obj) {
	var message = ret_obj['message'];
	var url = current_url;

	if(typeof(current_mid) != 'undefined') url = url.setQuery('mid', current_mid);
	if(typeof(xeVid) != 'undefined') url = url.setQuery('vid', xeVid);
	url = url.setQuery('act', 'dispAllianceManageSync');
	url = url.setQuery('site_srl', '');

	alert(message);
	location.href =  url;
}