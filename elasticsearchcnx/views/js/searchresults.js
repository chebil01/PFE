$(document).ready(function() {
    var searchUrl = $('#searchResultsContainer').data('resultf-url');
    var searchQuery = $('#searchResultsContainer').data('search-query');
    console.log(searchQuery);
    console.log(searchUrl);
    function displaySearchResults(results) {
        $('#searchResultsContainer').empty();
        var $resultCount = $('<div>').addClass('result-count').text('Il y a '+ results.length+'  produits.' );
        $('#searchResultsContainer').append($resultCount).append('<br>');  
        var $productsContainer = $('<div>').addClass('products-container');
        for (var i = 0; i < results.length; i++) {
            var product = results[i];
            var $productNameLink = $('<a>').attr('href', product.product_link).append(product.product_name);
            var $product = $('<article>')
              .addClass('product-container')
              .attr('data-id-product', product.id)
              .attr('data-id-product-attribute', product.attribute);
            
              var $productImage = $('<a>')
              .addClass('product-image-link')
              .attr('href', product.product_link)
              .append(
                $('<img>')
                  .addClass('product-image')
                  .attr('src', product.image)
                  .attr('alt', product.product_name)
              );
            
            var $productInfo = $('<div>').addClass('product-info');
            var $productName = $('<h2>').addClass('product-name').append($productNameLink);
            var $productDescription = $('<div>').addClass('product-description').append(product.product_description);
            var $productPrice = $('<div>').addClass('product-price').text(product.price +' DT');
            
            $productInfo.append($productName, $productDescription, $productPrice);
            $product.append($productImage, $productInfo);
            $productsContainer.append($product);
            
          }
          $('#searchResultsContainer').append($productsContainer);
          
        
    }
    function updateSearchResults() {
        var searchQuery = $('#searchBar').val().trim();
        var categoryOption = $('#categoryOption').val();
        var sortOption = $('#sortOption').val();
        if ( searchQuery.length >= 3|| categoryOption !== '') {
            var updatedSearchUrl = searchUrl;
            if (searchQuery.length >= 3) {
                updatedSearchUrl +=  '?search_string=' + encodeURIComponent(searchQuery);
            }
    
            if (categoryOption !== '' && searchQuery.length >= 3) {
                updatedSearchUrl += '&category=' + encodeURIComponent(categoryOption);
            }
            if (categoryOption !== '' && searchQuery.length < 3)
            {
                updatedSearchUrl += '?category=' + encodeURIComponent(categoryOption);
            }

            if (sortOption === 'asc' || sortOption === 'desc') {
                updatedSearchUrl += '&sort=' + sortOption;
              }

            history.replaceState(null, null, updatedSearchUrl);

            $.ajax({
                url: updatedSearchUrl,
                method: 'GET',
                dataType: 'json',
                data: {
                    ajax: 1
                },
                success: function(response) {
                    console.log(response);
                    if (response.length > 0) {
                        displaySearchResults(response);
                    } else {
                        var noResultsHTML = $('<div>').addClass('No-result').text('No results found.' );
                        $('#searchResultsContainer').html(noResultsHTML);
                    }
                },
                error: function() {
                    console.error('Error occurred during search request.');
                    var errorHTML = '<p>Error</p>';
                    $('#searchResultsContainer').html(errorHTML);
                }
            });
        } else {
            history.replaceState(null, null, searchUrl);
            var $null = $('<div>').addClass('saisr-null').text('saisir plus de 3 caractére ou sélectionner catégorie.' );
            $('#searchResultsContainer').empty().html($null);
        }
    }

    updateSearchResults();
    $('#searchBar').on('input', function() {
        updateSearchResults();
    });
    $('#sortOption').on('change', function() {
        updateSearchResults();
    });
    $('#categoryOption').on('change', function() {
        updateSearchResults();
    });
    $('#searchButton').on('click', function() {
        updateSearchResults();
    });

});
