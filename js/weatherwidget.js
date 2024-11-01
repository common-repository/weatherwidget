(function(){var url = window.weatherwidget_url;if(typeof(geoip_city)=='function'){url+='&city='+geoip_city()+'&language='+geoip_country_code();}document.write('<'+'iframe height="172" frameborder="0" width="160" scrolling="no" vspace="0" src="'+url+'" name="weatherwidget" marginwidth="0" marginheight="0" hspace="0" allowtransparency="true"'+'>'+'<'+'/iframe'+'>');
})();
