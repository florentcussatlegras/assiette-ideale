
// import Routing from 'fos-router';

// const removePic = document.querySelectorAll('.remove-pic');

// removePic.forEach((element) => {
//     element.addEventListener('click', (event) => {
//         event.preventDefault();

//         if(window.confirm('Etes-vous sûr de vouloir supprimer cette image?')) {

//             let url = Routing.generate('app_pic_dish_delete', {
//                             rank: element.dataset.rank, 
//                             dish_id: element.dataset.dishid
//                         });

//             window.location.href = url;
//         }
//     });
// });

const gridBtn = document.getElementById('gridBtn');
const listBtn = document.getElementById('listBtn');

if(gridBtn && listBtn) {	
	gridBtn.addEventListener('click', clickButton);
	listBtn.addEventListener('click', clickButton);
}

function clickButton(event) {
	event.preventDefault();
	let newStyle = event.target.id;
    alert(newStyle);
	newStyle = newStyle.replace('Btn', '');
	const element = document.getElementById("card-wrapper");
    element.classList.remove("grid");
	element.classList.remove("list");
	element.classList.add(newStyle);
}

