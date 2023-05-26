{extends file='page.tpl'}

{block name='page_content'}
    <nav data-depth="2" class="breadcrumb hidden-sm-down">
    <ol>
        <li>
            <a href="{$accueil_link}"><span>{$accueil_name}</span></a>
        </li>
        <li>
            <span>Résultats de la recherche</span>
        </li>
    </ol>
</nav>

<div class="col-lg-9">
    <h2>Résultats de la recherche</h2>
    <div class="sort-container">
        <label for="sortOption">Sort by:</label>
        <select id="sortOption">
            <option value=""></option>
            <option value="asc">Price: Low to High</option>
            <option value="desc">Price: High to Low</option>
        </select>
        <br>
        <label for="sortOption">Filter by:</label>
        <select id="categoryOption">
            <option value="">Category</option>
            {foreach $categories as $category}
                <option value="{$category.name}">{$category.name}</option>
            {/foreach}
        </select>
    </div>
    <div class="search-container">
        <input type="text" id="searchBar" value="{$search_string}">
        <button id="searchButton">Search</button>
    </div>
    <div id="searchResultsContainer" data-resultf-url="{$search_url}" data-search-query="{$search_string}"></div>
</div>

{/block}
