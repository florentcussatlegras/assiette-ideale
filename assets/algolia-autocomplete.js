import { autocomplete, getAlgoliaResults } from '@algolia/autocomplete-js';
import algoliasearch from 'algoliasearch';
// import { toggleModal } from './liveforeat-modal';

import '@algolia/autocomplete-theme-classic';

const searchClient = algoliasearch(
    '2FW1PQIBDL',
    '0dc83feac400884e2488caa6aa1f197d'
  );
  
  autocomplete({
    container: '#autocomplete',
    placeholder: 'Cherchez un aliment',
    getSources({ query }) {
      return [
        {
          sourceId: 'foods',
          getItems() {
            return getAlgoliaResults({
              searchClient,
              queries: [
                {
                  indexName: 'dev_foods',
                  query,
                  params: {
                    hitsPerPage: 10
                  },
                },
              ],
            });
          },
          templates: {
            item({ item, components, createElement }) {
              return createElement(
                'div',
                { className: 'aa-ItemWrapper' },
                createElement(
                  'div',
                  { className: 'aa-ItemContent' },
                  createElement(
                    'div',
                    { className: 'aa-ItemIcon aa-ItemIcon--alignTop h-10 w-10 p-0' },
                    createElement('img', {
                      src: "https://localhost:8000/uploads/food/" + item.picture,
                      alt: item.name,
                      className: 'h-full w-full'
                    })
                  ),
                  createElement(
                    'div',
                    { className: 'aa-ItemContentBody' },
                    createElement(
                      'div',
                      { className: 'aa-ItemContentTitle' },
                      components.Snippet({ hit: item, attribute: 'name' })
                    )
                  ),
                  createElement(
                    'div',
                    { className: 'aa-ItemActions ml-auto' },
                    createElement(
                      'button',
                      {
                        className:
                          'aa-ItemActionButton aa-DesktopOnly aa-ActiveOnly',
                        type: 'button',
                        title: 'Select',
                        onClick: (event) => {
                          event.stopPropagation();
                          modalQuantity(item);
                          toggleModal();
                        }
                      },
                      createElement(
                        'svg',
                        {
                          viewBox: '0 0 24 24',
                          width: 20,
                          height: 20,
                          fill: 'currentColor',
                        },
                        createElement('path', {
                          d: 'M18.984 6.984h2.016v6h-15.188l3.609 3.609-1.406 1.406-6-6 6-6 1.406 1.406-3.609 3.609h13.172v-4.031z',
                        })
                      )
                    ),
                  )
                )
              );
            },
          },
          getItemUrl({item}) {
              return item.name;
          },
        },
      ];
    },
  });

  function modalQuantity(item) {
    document.getElementById('modal-food-id').value = item.id;
    document.getElementById('modal-food-name').innerHTML = item.name;
    // const body = document.querySelector('body');
    // const modal = document.querySelector('.modal');
    // modal.classList.toggle('opacity-0');
    // modal.classList.toggle('pointer-events-none');
    //body.classList.toggle('modal-active');
  }
              
  
  
                
                

