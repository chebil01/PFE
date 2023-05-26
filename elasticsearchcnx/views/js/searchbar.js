$(document).ready(function() {
  var $input = $('#search-input');
  var $form = $('#search-form');
  var $searchDialog = $('#search-dialog');
  var $submit = $('#submit-search');
  var baseUrl = window.location.protocol + "//" + window.location.host;

  function searchSuggestions(query) {
    var suggestionsUrl = $searchDialog.data('search-url');
    var searchUrl = suggestionsUrl + '?search_string=' + encodeURIComponent(query);
    $searchDialog.find('.search-results-list').empty().append('<p>Loading search results...</p>');
    console.log(searchUrl);
    $.ajax({
      url: searchUrl,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        var products = data.products;
        var $list = $searchDialog.find('.search-results-list').empty();
        console.log(data.products);
        if (products.length > 0) {
          var $resultCount = $('<div>').addClass('result-count').text('Le résultat de la recherche pour "' + query + '" (' + products.length + ' résultats ont été trouvés.)');
          $list.append($resultCount);
          for (var i = 0; i < 5 && i < products.length; i++) {
            var product = products[i];
            var imageSrc = baseUrl + product.image;
            var $li = $('<li>').addClass('search-result');

            var $productInfo = $('<div>').addClass('product-info');
            var $resultImage = $('<img>', { src: imageSrc, alt: product.product_name }).addClass('product-image');
            $productInfo.append($resultImage);
            var $productLink = $('<a>').attr('href', product.product_link).text(product.product_name).addClass('product-link');
            var $productName = $('<span>').addClass('product-name').append($productLink);
            $productInfo.append($productName);
            var $priceval = parseFloat(product.price);
            var $price = $('<span>').addClass('search-result-price').text($priceval.toFixed(3));
            $productInfo.append($price);
            $li.append($productInfo);
            $list.append($li);
          }

          var $button = $('<button>').text('voir plus').addClass('search-result-button');
          $button.on('click', Searchdetails);
          $list.append($button);
        } else {
          $list.append('<p>No results found.</p>');
        }
      },
      error: function(xhr, status, error) {
        $searchDialog.find('.search-results-list').html('<p>Error loading search results.</p>');
      }
    });
  }

  function getMostFrequentProducts() {
    var suggestionsUrl = $searchDialog.data('search-url');
    var searchUrl = suggestionsUrl + '?get_products=true';
    $searchDialog.find('.search-results-list').empty().append('<p>Loading most frequent products...</p>');
    console.log(searchUrl);
    $.ajax({
      url: searchUrl,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        var products = data.suproducts;
        console.log(data.suproducts);
        var $list = $searchDialog.find('.search-results-list').empty();

        if (products.length > 0) {
          var $resultCount = $('<div>').addClass('result-count').text('Most frequent products:');
          $list.append($resultCount);
          for (var i = 0; i < 5 && i < products.length; i++) {
            var product = products[i];
            var imageSrc = baseUrl + product.image;
            var $li = $('<li>').addClass('search-result');

            var $productInfo = $('<div>').addClass('product-info');
            var $resultImage = $('<img>', { src: imageSrc, alt: product.product_name }).addClass('product-image');
            $productInfo.append($resultImage);
            var $productLink = $('<a>').attr('href', product.product_link).text(product.product_name).addClass('product-link');
            var $productName = $('<span>').addClass('product-name').append($productLink);
            $productInfo.append($productName);
            var $priceval = parseFloat(product.price);
            var $price = $('<span>').addClass('search-result-price').text($priceval.toFixed(3));
            $productInfo.append($price);
            $li.append($productInfo);
            $list.append($li);
          }
        } else {
          $list.append('<p>No most frequent products found.</p>');
        }
      },
      error: function(xhr, status, error) {
        $searchDialog.find('.search-results-list').html('<p>Error loading most frequent products.</p>');
      }
    });
  }

  function Searchdetails() {
    var query = $input.val();
    var searchUrl = $submit.data('result-url');
    var resultUrl = searchUrl + '?search_string=' + encodeURIComponent(query);
    window.location.href = resultUrl;
  }

  $input.on('input', function() {
    var query = $input.val();
    if (query.length >= 3) {
      searchSuggestions(query);
    } else {
      var $null = $('<div>').addClass('saisr-null').text('saisir plus de 3 caractére.');
      $searchDialog.find('.search-results-list').empty().html($null);
    }
  });

  $('#btn').on('click', function(e) {
    Searchdetails();
  });

  $('#search-btn').on('click', function(e) {
    e.preventDefault();
    $searchDialog.show();
    $input.focus();
    $('body').addClass('modal-open');
    getMostFrequentProducts();
  });

  $('#close-btn').on('click', function() {
    $searchDialog.hide();
    $('body').removeClass('modal-open');
  });
});
