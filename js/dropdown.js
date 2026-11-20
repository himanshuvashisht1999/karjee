document.addEventListener('DOMContentLoaded', function () {
    const dropdownInputs = document.querySelectorAll('input.dropdown');

    dropdownInputs.forEach(input => {
        // Create a container for options
        const dropdownList = document.createElement('div');
        dropdownList.className = 'dropdown-list';
        input.parentNode.insertBefore(dropdownList, input.nextSibling);

        // Populate the list with options from data-options
        const optionsData = input.getAttribute('data-options');
        if (optionsData) {
			const options = JSON.parse(optionsData); // Parse the JSON array
            //const options = optionsData.split(',').map(option => option.trim());
            options.forEach(option => {
                const optionElement = document.createElement('div');
                optionElement.className = 'dropdown-option';
                optionElement.textContent = option.displayText;
				optionElement.dataset.value = option.value;
                dropdownList.appendChild(optionElement);

                // Option click behavior
                optionElement.onclick = () => {
                    input.value = option.displayText;
					input.dataset.selectedValue = option.value;
                    dropdownList.style.display = 'none';
                };
            });
        }

        // Show/hide dropdown on input focus/blur
        /* input.onfocus = () => {
            dropdownList.style.display = 'block';
        }; */

        input.onblur = () => {
            setTimeout(() => dropdownList.style.display = 'none', 200);
        };

        // Filter options on input
		 input.oninput = () => {
			const filter = input.value.toUpperCase();
			const options = dropdownList.querySelectorAll('.dropdown-option');
			let matchFound = false; // Flag to check if a match is found
			if (filter) {
				let matchFound = false; // Track if any match is found

				options.forEach(option => {
					if (option.textContent.toUpperCase().includes(filter)) {
						option.style.display = 'block'; // Show matching options
						matchFound = true;
					} else {
						option.style.display = 'none'; // Hide non-matching options
					}
				});

				if (matchFound) {
					dropdownList.style.display = 'block'; // Show dropdown if matches are found
				} else {
					dropdownList.style.display = 'none'; // Hide dropdown if no matches
				}
			} else {
				// Restore all options if the filter is empty
				options.forEach(option => {
					option.style.display = 'block';
				});
				dropdownList.style.display = 'block'; // Ensure the dropdown is visible
			}
		};


        // Keyboard navigation
        let currentFocus = -1;
        input.onkeydown = (e) => {
			const options = Array.from(dropdownList.querySelectorAll('.dropdown-option')).filter(option => {
				return window.getComputedStyle(option).display !== 'none';
			});
            if (e.key === 'ArrowDown') {
				if(input.value.toUpperCase()==""){
					dropdownList.style.display = 'block';
					currentFocus = (currentFocus + 1) % options.length;
					addActive(options);
				}
				else{
					
					//currentFocus = (currentFocus + 1) % options.length;
					if(dropdownList.style.display !== 'block'){
						addActive(options);	
						dropdownList.style.display = 'block';
					}
					else{
						currentFocus = (currentFocus + 1) % options.length;
						addActive(options);	
					}
					
				}
            } else if (e.key === 'ArrowUp') {
                currentFocus = (currentFocus - 1 + options.length) % options.length;
                addActive(options);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentFocus > -1) {
                    options[currentFocus].click();
                }
            } else if (e.key === 'Escape') {
                dropdownList.style.display = 'none';
            } else if (e.key === 'Backspace' || e.key === 'Delete') {
				if(input.value.toUpperCase().length==1){
					removeActive(options);
					displayAll(options);
					currentFocus = -1;
				}
            }
        };

        function addActive(options) {
            if (!options) return;
            removeActive(options);
            if (currentFocus >= options.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = options.length - 1;
            options[currentFocus].classList.add('active');
        }

        function removeActive(options) {
            options.forEach(option => option.classList.remove('active'));
        }
		function displayAll(options) {
            options.forEach(option => option.style.display ='block');
        }
    });
	HTMLInputElement.prototype.setSelected = function ({ value = null, displayText = null }) {
		const dropdownList = this.nextElementSibling; // Assuming dropdown list is next to input
		if (!dropdownList || !dropdownList.classList.contains('dropdown-list')) {
			console.warn('Dropdown list not found.');
			return;
		}

		if (!value && !displayText) {
			console.warn('Either value or displayText must be provided.');
			return;
		}

		const options = dropdownList.querySelectorAll('.dropdown-option');
		let matchedOption = null;

		// Find the matching option by value or displayText
		options.forEach(option => {
			if (
				(value && option.dataset.value === value) || 
				(displayText && option.textContent === displayText)
			) {
				matchedOption = option;
			}
		});

		if (matchedOption) {
			// Set the input value and update the selected value in the dataset
			this.value = matchedOption.textContent;
			this.dataset.selectedValue = matchedOption.dataset.value;
			dropdownList.style.display = 'none'; // Optional: Hide dropdown
		} else {
			console.warn('No matching option found for value or displayText:', value || displayText);
		}
	};

	HTMLInputElement.prototype.getSelectedValue = function () {
		return this.dataset.selectedValue || null;
	};

	HTMLInputElement.prototype.getSelectedDisplayText = function () {
		return this.value || null;
	};
});
