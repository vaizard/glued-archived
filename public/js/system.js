
// WIKI

// ulozeni rozeditovaneho clanku pres ajax
function wiki_save_article(article_id, save_url, article_markup_source, article_html_source) {
    article_markup = $("#" + article_markup_source).text();
    article_html = $("#" + article_html_source).html();
    $.ajax({
      url: save_url,
      dataType: "text",
      type: "PUT",
      data: "article_id=" + article_id + "&article_markup=" + article_markup + "&article_html=" + article_html,
      success: function(data) {
        window.open("https://stackedit.industra.space/static/landing/inc.html", "resetIDB");
        alert("changes were saved");
      },
      error: function(xhr, status, err) {
        alert("warning: changes were not saved");
      }
    });
    
}