const searchClient = algoliasearch('2FW1PQIBDL', '0dc83feac400884e2488caa6aa1f197d');

const search = instantsearch({
  indexName: 'dev_foods',
  searchClient,
});

search.addWidgets([
  instantsearch.widgets.searchBox({
    container: '#searchbox',
  }),

  instantsearch.widgets.hits({
    container: '#hits',
  })
]);

search.start();
