function getBlogRss() {
	return true;
	$.ajax({
		url : dashboard_ajax_url,
		data : {
			ajax:true,
			action:'getBlogRss'
		},
		dataType: 'json',
		success : function(jsonData) {
			if (typeof jsonData !== 'undefined' && jsonData !== null && !jsonData.has_errors) {
				for (var article in jsonData.rss) {
					var article_html = '<article><h4><a href="'+jsonData.rss[article].link+'" class="_blank" onclick="return !window.open(this.href);">'+jsonData.rss[article].title+'</a></h4><span class="dash-news-date text-muted">'+jsonData.rss[article].date+'</span><p>'+jsonData.rss[article].short_desc+' <a href="'+jsonData.rss[article].link+'">'+read_more+'</a><p></article><hr/>';
					$('.dash_news .dash_news_content').append(article_html);
				}
			}
			else {
				$('.dash_news').hide();
			}
		}
	});
}