const details = document.querySelectorAll("details");
details.forEach((targetDetail) => {
	targetDetail.addEventListener("click", () => {
		details.forEach((detail) => {
			if (detail !== targetDetail) {
				if(!detail.classList.contains("alwaysopen")){
					detail.removeAttribute("open");
				}
			}
		});
	}); 
});