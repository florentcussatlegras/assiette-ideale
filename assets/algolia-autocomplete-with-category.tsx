import * as preact from 'preact';

// Parcel picks the `source` field of the monorepo packages and thus doesn't
// apply the Babel config. We therefore need to manually override the constants
// in the app, as well as the React pragmas.
// See https://twitter.com/devongovett/status/1134231234605830144
(global as any).__DEV__ = process.env.NODE_ENV !== 'production';
(global as any).__TEST__ = false;
(global as any).h = preact.h;
(global as any).React = preact;

import { autocomplete } from '@algolia/autocomplete-js';
import { createQuerySuggestionsPlugin } from '@algolia/autocomplete-plugin-query-suggestions';
import algoliasearch from 'algoliasearch';

import '@algolia/autocomplete-theme-classic';

const appId = '2FW1PQIBDL';
const apiKey = '0dc83feac400884e2488caa6aa1f197d';
const searchClient = algoliasearch(appId, apiKey);

const querySuggestionsPlugin = createQuerySuggestionsPlugin({
  searchClient,
  indexName: 'dev_foods_query_suggestions',
  getSearchParams() {
    return {
      hitsPerPage: 10,
    };
  }
});

autocomplete({
  container: '#autocomplete',
  placeholder: 'Search foods',
  openOnFocus: true,
  plugins: [querySuggestionsPlugin],
});

// ,
//   categoryAttribute: [
//     'dev_foods',
//     'facets',
//     'exact_matches',
//     'categories',
//   ],
//   itemsWithCategories: 1,
//   categoriesPerItem: 2,