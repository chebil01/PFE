<button id="search-btn"><i class="material-icons search" aria-hidden="true">search</i></button>

<div id="search-dialog" data-search-url="{$search_controller_url}" title="{l s='Search Results' d='Shop.Theme.Catalog'}">
  <div class="search-results">
    <div class="top-bar">
      <button id="close-btn">Close</button>
    </div>
    <div class="search-input-container">
      <input type="text" id="search-input" class="search-input" placeholder="{l s='Search our catalog' d='Shop.Theme.Catalog'}">
      <div id="submit-search" data-result-url="{$resultsearch_controller_url}">
        <button type="submit" id="btn">Search</button>
      </div>
    </div>
    <ul class="search-results-list"></ul>
  </div>
</div>