
const dishesEl = document.querySelector('.dishes')
const loader = document.querySelector('.loader')

let currentPage = 0
const limit = 20
let total = 0

const hideLoader = () => {
  loader.classList.remove('show')
}

const showLoader = () => {
  loader.classList.add('show')
}

const hasMoreDishes = (page, limit, total) => {
  const startIndex = (page - 1) * limit + 1
  return total === 0 || startIndex < total
};

const getDishes = async (page, limit) => {
  const r = await fetch('https://localhost:8000/plat/list_json/' + limit + '/' + page, {
      method: 'GET',
      headers: {
        'Accept': "application/json"
      }
  })
  if(r.ok === true) {
    return r.json();
  }
  throw new Error('Impossible de contacter le serveur');
}

const showDishes = (dishes) => {
  dishes.forEach(dish => {
    const dishEl = document.createElement('div')
    const dishImageUrl = "https://localhost:8000/uploads/media/cache/squared_thumbnail_medium/dish/" + dish.pictures[0].name
    const dishUrl = Routing.generate('app_dish_show', {
      'id': dish.id,
      'slug': dish.slug
    })
    dishEl.innerHTML = `
        <a href="` + dishUrl + `"> 
          <div class="file bg-white pb-5 relative overflow-hidden p-6 hover:bg-grey-100 border-r border-b">
            <div class="file__icon--image__preview image-fit h-52">
                <img class="rounded-md h-full" alt="Photo du plat ` + dish.name + `" src="` + dishImageUrl + `">
            </div>
            <div>
                <span class="block text-xl font-bold mt-4 truncate">` + dish.name + `</span>
                <div class="text-gray-600 text-xs mt-0.5">1 MB</div>
            </div>
          </div>
        </a>
      `
    
    dishesEl.appendChild(dishEl)
  })
}

const loadDishes = async (page, limit) => {

    showLoader()

    try {
      // if(hasMoreDishes(page, limit, total)) {
        const reponse = await getDishes(page, limit)
        showDishes(reponse)
        total = reponse.total
      // }
    } catch (error) {
      console.log(error.message)
    } finally {
      hideLoader()
    }

}

loadDishes(currentPage, limit)

function delay(callback, ms) {
  var timer = 0;
  return function() {
    var context = this, args = arguments;
    clearTimeout(timer);
    timer = setTimeout(function () {
      callback.apply(context, args);
    }, ms || 0);
  };
}

window.addEventListener('scroll', delay(() => {

    const {
      scrollTop,
      scrollHeight,
      clientHeight
    } = document.documentElement

    // if (scrollTop + clientHeight >= scrollHeight - 5 && hasMoreDishes(currentPage, limit, total)) {
    if (scrollTop + clientHeight >= scrollHeight - 5) {
      console.log('load dishes')
      currentPage++
      loadDishes(currentPage, limit)
    }

  }, 500), {
  passive: true
})











