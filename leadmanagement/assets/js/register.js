// assets/js/register.js – Handles dynamic dropdowns and form submission with searchable select features

const API_BASE = "api/";

// Global references to searchable dropdown controls
let stateDropdown = null;
let districtDropdown = null;
let productDropdown = null;
let stateList = [];

// Helper to show modern micro-toasts
function showToast(message, type = 'info') {
    const existing = document.querySelector('.alert-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `alert-toast ${type}`;
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const iconColor = type === 'success' ? '#10b981' : '#f59e0b';
    
    toast.innerHTML = `
        <i class="fa-solid ${icon}" style="color: ${iconColor}; font-size: 1.2rem;"></i>
        <span style="font-weight: 500;">${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideUp 0.3s forwards reverse';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Reusable Searchable Dropdown setup
function setupDropdownSearch(wrapperId, searchInputId, hiddenInputId, optionsContainerId, listData, onSelectCallback) {
    const wrapper = document.getElementById(wrapperId);
    const searchInput = document.getElementById(searchInputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const optionsContainer = document.getElementById(optionsContainerId);

    function populateOptions(items) {
        optionsContainer.innerHTML = '';
        if (items.length === 0) {
            optionsContainer.innerHTML = `<div class="custom-option no-results">No results found</div>`;
            return;
        }
        items.forEach(item => {
            const optDiv = document.createElement('div');
            optDiv.className = 'custom-option';
            if (hiddenInput.value === String(item.id)) {
                optDiv.classList.add('selected');
            }
            optDiv.dataset.id = item.id;
            optDiv.dataset.name = item.name;
            optDiv.innerHTML = `
                <span>${item.name}</span>
                ${hiddenInput.value === String(item.id) ? '<i class="fa-solid fa-check" style="color: var(--primary-accent); font-size: 0.8rem;"></i>' : ''}
            `;
            
            optDiv.addEventListener('click', (e) => {
                e.stopPropagation();
                searchInput.value = item.name;
                hiddenInput.value = item.id;
                
                optionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
                optDiv.classList.add('selected');
                
                wrapper.classList.remove('open');
                if (onSelectCallback) onSelectCallback(item.id);
            });
            optionsContainer.appendChild(optDiv);
        });
    }

    // Toggle dropdown
    searchInput.addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.custom-select-wrapper').forEach(w => {
            if (w.id !== wrapperId) w.classList.remove('open');
        });
        wrapper.classList.toggle('open');
        filterOptions(searchInput.value);
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });

    // Real-time keyword filter
    function filterOptions(term) {
        const query = term.toLowerCase().trim();
        const filtered = listData.filter(item => item.name.toLowerCase().includes(query));
        populateOptions(filtered);
    }

    searchInput.addEventListener('input', (e) => {
        if (!wrapper.classList.contains('open')) {
            wrapper.classList.add('open');
        }
        filterOptions(e.target.value);
    });

    // Dynamic Self-Healing Auto-Select on blur
    searchInput.addEventListener('blur', () => {
        const val = searchInput.value.trim().toLowerCase();
        if (!val) {
            hiddenInput.value = '';
            return;
        }

        // Check if current ID already matches the text
        const currentSelected = listData.find(item => String(item.id) === String(hiddenInput.value));
        if (currentSelected && currentSelected.name.toLowerCase() === val) {
            return; // Already matched correctly
        }

        // Try exact match first
        const exactMatch = listData.find(item => item.name.toLowerCase() === val);
        if (exactMatch) {
            hiddenInput.value = exactMatch.id;
            searchInput.value = exactMatch.name;
            if (onSelectCallback) onSelectCallback(exactMatch.id);
        } else {
            // Try single unambiguous partial match
            const partialMatches = listData.filter(item => item.name.toLowerCase().includes(val));
            if (partialMatches.length === 1) {
                hiddenInput.value = partialMatches[0].id;
                searchInput.value = partialMatches[0].name;
                if (onSelectCallback) onSelectCallback(partialMatches[0].id);
            }
        }
    });

    // Populate initially
    populateOptions(listData);
    
    return {
        updateData: (newData) => {
            listData = newData;
            populateOptions(newData);
            searchInput.value = '';
            hiddenInput.value = '';
        },
        reset: () => {
            searchInput.value = '';
            hiddenInput.value = '';
            optionsContainer.innerHTML = '';
        },
        enable: () => {
            searchInput.removeAttribute('disabled');
        },
        disable: () => {
            searchInput.setAttribute('disabled', 'true');
        },
        getMatchedItem: (term) => {
            const query = term.toLowerCase().trim();
            if (!query) return null;
            const exact = listData.find(item => item.name.toLowerCase() === query);
            if (exact) return exact;
            const partials = listData.filter(item => item.name.toLowerCase().includes(query));
            if (partials.length === 1) return partials[0];
            return null;
        }
    };
}

// Populate State dropdown on page load
async function loadStates() {
    try {
        stateList = await fetchJSON(API_BASE + 'states.php');
        stateDropdown = setupDropdownSearch(
            'state-wrapper', 
            'state-search', 
            'state', 
            'state-options', 
            stateList, 
            (stateId) => {
                loadDistricts(stateId);
            }
        );
    } catch (e) {
        console.error('Failed to load states:', e);
        showToast('Error loading States list', 'error');
    }
}

// Populate District dropdown based on selected state
async function loadDistricts(stateId, selectedDistrictId = null, selectedDistrictName = null) {
    if (!stateId) {
        if (districtDropdown) {
            districtDropdown.reset();
            districtDropdown.disable();
        }
        return;
    }
    try {
        const districts = await fetchJSON(`${API_BASE}districts.php?state_id=${stateId}`);
        if (!districtDropdown) {
            districtDropdown = setupDropdownSearch(
                'district-wrapper',
                'district-search',
                'district',
                'district-options',
                districts
            );
        } else {
            districtDropdown.updateData(districts);
        }
        districtDropdown.enable();

        if (selectedDistrictId || selectedDistrictName) {
            let matchedD = districts.find(d => String(d.id) === String(selectedDistrictId));
            if (!matchedD && selectedDistrictName) {
                const normName = selectedDistrictName.toLowerCase().trim();
                matchedD = districts.find(d => {
                    const dName = d.name.toLowerCase().trim();
                    return dName === normName || dName.includes(normName) || normName.includes(dName) || dName.substring(0, 4) === normName.substring(0, 4);
                });
            }

            if (matchedD) {
                document.getElementById('district').value = matchedD.id;
                document.getElementById('district-search').value = matchedD.name;
            } else {
                document.getElementById('district').value = selectedDistrictId;
                document.getElementById('district-search').value = selectedDistrictName;
            }
        }
    } catch (e) {
        console.error('Failed to load districts:', e);
        showToast('Error loading districts for this state', 'error');
    }
}

// Populate Product dropdown on page load
async function loadProducts() {
    try {
        const products = await fetchJSON(API_BASE + 'products.php');
        productDropdown = setupDropdownSearch(
            'product-wrapper',
            'product-search',
            'product',
            'product-options',
            products
        );
    } catch (e) {
        console.error('Failed to load products:', e);
        showToast('Error loading products dropdown', 'error');
    }
}

// Generic GET helper
async function fetchJSON(url) {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error(`Network error: ${resp.status}`);
    return await resp.json();
}

// Search, overlay render, and keyboard navigation for Client Master on Contact Number field
function setupContactClientSearch() {
    const contactInput = document.getElementById('contact');
    const contactOptions = document.getElementById('contact-options');
    let activeIndex = -1;
    let matchingClients = [];

    function closeList() {
        contactOptions.innerHTML = '';
        contactOptions.style.display = 'none';
        activeIndex = -1;
    }

    function renderSuggestions(clients) {
        matchingClients = clients;
        contactOptions.innerHTML = '';
        activeIndex = -1;

        if (clients.length === 0) {
            contactOptions.style.display = 'none';
            return;
        }

        clients.forEach((client, idx) => {
            const optDiv = document.createElement('div');
            optDiv.className = 'custom-option';
            optDiv.dataset.index = idx;
            optDiv.innerHTML = `
                <div style="width: 100%; display: flex; flex-direction: column; text-align: left; gap: 2px;">
                    <span style="font-weight: 600; color: var(--primary-accent);">${client.client_name}</span>
                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                        <i class="fa-solid fa-phone" style="font-size: 0.75rem; margin-right: 4px;"></i>${client.phones}
                        <span style="margin: 0 6px;">|</span>
                        <i class="fa-solid fa-location-dot" style="font-size: 0.75rem; margin-right: 4px;"></i>${client.city || '-'}, ${client.districtname || '-'}, ${client.statename || '-'}
                    </span>
                </div>
            `;

            optDiv.addEventListener('click', () => {
                selectClient(client);
            });

            contactOptions.appendChild(optDiv);
        });

        contactOptions.style.display = 'block';
    }

    function selectClient(client) {
        document.getElementById('name').value = client.client_name;
        contactInput.value = client.phones;
        
        let stateId = client.state_id;
        let stateName = client.statename;

        // Self-healing state fuzzy match from global stateList if ID is empty
        if (!stateId && stateName && stateList.length > 0) {
            const normState = stateName.toLowerCase().trim();
            const matchedS = stateList.find(s => {
                const sName = s.name.toLowerCase().trim();
                return sName === normState || sName.includes(normState) || normState.includes(sName) || sName.substring(0, 3) === normState.substring(0, 3);
            });
            if (matchedS) {
                stateId = matchedS.id;
                stateName = matchedS.name;
            }
        }
        
        // Populate state values
        if (stateId) {
            document.getElementById('state').value = stateId;
            document.getElementById('state-search').value = stateName;
            
            // Populate district cascade dynamically with dynamic self-healing lookup
            loadDistricts(stateId, client.district_id, client.districtname);
        }
        
        // Populate city
        document.getElementById('city').value = client.city || '';
        
        closeList();
        showToast('Client loaded from master list!', 'success');
    }

    contactInput.addEventListener('input', async (e) => {
        const val = e.target.value.trim();
        if (val.length < 5) {
            closeList();
            return;
        }

        try {
            const resp = await fetch(`${API_BASE}search_clients.php?q=${encodeURIComponent(val)}`);
            if (resp.ok) {
                const data = await resp.json();
                renderSuggestions(data);
            }
        } catch (err) {
            console.error('Error fetching client search:', err);
        }
    });

    contactInput.addEventListener('keydown', (e) => {
        const options = contactOptions.querySelectorAll('.custom-option');
        if (options.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex++;
            if (activeIndex >= options.length) activeIndex = 0;
            updateHighlight(options);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex--;
            if (activeIndex < 0) activeIndex = options.length - 1;
            updateHighlight(options);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && activeIndex < options.length) {
                e.preventDefault();
                options[activeIndex].click();
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    function updateHighlight(options) {
        options.forEach((opt, idx) => {
            if (idx === activeIndex) {
                opt.classList.add('selected');
                opt.scrollIntoView({ block: 'nearest' });
            } else {
                opt.classList.remove('selected');
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (!contactInput.contains(e.target) && !contactOptions.contains(e.target)) {
            closeList();
        }
    });
}
// Selected products tracking state array
let selectedProducts = [];

// Handles dynamic adding and deleting of products in the list
function setupMultiProductHandlers() {
    const btnAddProduct = document.getElementById('btn-add-product');
    const productInput = document.getElementById('product');
    const productSearchInput = document.getElementById('product-search');
    const quantityInput = document.getElementById('quantity');
    const addedProductsList = document.getElementById('added-products-list');

    if (!btnAddProduct) return;

    btnAddProduct.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Self-healing: if hidden ID is empty but search box has a text value, try to resolve it dynamically
        let productId = productInput.value;
        let productName = productSearchInput.value.trim();
        
        if (!productId && productName && productDropdown && typeof productDropdown.getMatchedItem === 'function') {
            const matched = productDropdown.getMatchedItem(productName);
            if (matched) {
                productId = matched.id;
                productName = matched.name;
                productInput.value = matched.id;
                productSearchInput.value = matched.name;
            }
        }
        
        const quantity = parseInt(quantityInput.value);

        if (!productId || !productName) {
            Swal.fire({
                icon: 'warning',
                title: 'Select Product',
                text: 'Please select a product from the searchable dropdown.',
                confirmButtonColor: 'var(--primary-accent)',
                timer: 4000
            });
            return;
        }

        if (isNaN(quantity) || quantity <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Quantity',
                text: 'Please specify a valid quantity of 1 or more.',
                confirmButtonColor: 'var(--primary-accent)',
                timer: 4000
            });
            return;
        }

        // Duplicate check
        const isDuplicate = selectedProducts.some(p => String(p.product_id) === String(productId));
        if (isDuplicate) {
            Swal.fire({
                icon: 'info',
                title: 'Already Added',
                text: 'This product has already been added to your enquiry list. Delete it first if you wish to adjust the quantity.',
                confirmButtonColor: 'var(--primary-accent)',
                timer: 5000
            });
            return;
        }

        // Push product to array
        selectedProducts.push({
            product_id: productId,
            product_name: productName,
            quantity: quantity
        });

        // Reset inputs
        productInput.value = '';
        productSearchInput.value = '';
        quantityInput.value = '';

        renderSelectedProducts();
        showToast('Product added successfully!', 'success');
    });

    // Global deletion hook
    window.removeProduct = function (productId) {
        selectedProducts = selectedProducts.filter(p => String(p.product_id) !== String(productId));
        renderSelectedProducts();
        showToast('Product removed from enquiry', 'info');
    };

    function renderSelectedProducts() {
        addedProductsList.innerHTML = '';
        if (selectedProducts.length === 0) {
            addedProductsList.innerHTML = `
                <tr class="empty-product-row">
                    <td colspan="3" style="text-align: center; color: var(--text-light); padding: 1.5rem 0;">No products added to this enquiry yet. Please add at least one.</td>
                </tr>
            `;
            return;
        }

        selectedProducts.forEach(p => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';
            tr.innerHTML = `
                <td style="padding: 10px 12px; text-align: left; font-weight: 500; color: var(--text-color);">${p.product_name}</td>
                <td style="padding: 10px 12px; text-align: center; font-weight: 600; color: var(--primary-accent);">${p.quantity}</td>
                <td style="padding: 10px 12px; text-align: center;">
                    <button type="button" class="btn-delete-product" onclick="removeProduct('${p.product_id}')" style="background: rgba(239, 68, 68, 0.1); border: none; color: #ef4444; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition);">
                        <i class="fa-solid fa-trash-can" style="font-size: 0.85rem;"></i>
                    </button>
                </td>
            `;

            // Hover effects on dynamically created delete button
            const btnDel = tr.querySelector('.btn-delete-product');
            btnDel.addEventListener('mouseenter', () => {
                btnDel.style.background = '#ef4444';
                btnDel.style.color = '#fff';
            });
            btnDel.addEventListener('mouseleave', () => {
                btnDel.style.background = 'rgba(239, 68, 68, 0.1)';
                btnDel.style.color = '#ef4444';
            });

            addedProductsList.appendChild(tr);
        });
    }
}

// Form submit handler with custom loading spinner
async function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;

    // Must have at least 1 product
    if (selectedProducts.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Products Added',
            text: 'Please select and add at least one product to the list before submitting.',
            confirmButtonColor: 'var(--primary-accent)'
        });
        return;
    }

    const submitBtn = document.getElementById('submit-btn');
    const originalBtnHTML = submitBtn.innerHTML;

    // Set loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span class="spinner"></span> <span style="margin-left: 8px;">Registering Enquiry...</span>`;

    const formData = new FormData(form);
    const payload = {};
    formData.forEach((value, key) => { payload[key] = value; });

    // Inject array of products
    payload.products = selectedProducts;

    try {
        const resp = await fetch(API_BASE + 'register_lead.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await resp.json();

        if (result.success) {
            // Elegant Swal alert showing the generated follow-up token number
            Swal.fire({
                icon: 'success',
                title: 'Enquiry Saved!',
                html: `
                    <p style="font-size: 1.02rem; margin-bottom: 12px; color: var(--text-color);">The lead has been recorded with <strong>${selectedProducts.length}</strong> product item(s).</p>
                    <div style="background: rgba(79, 70, 229, 0.08); border: 2px dashed var(--primary-accent); padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 1.35rem; font-family: monospace; color: var(--primary-accent); letter-spacing: 1.5px; display: inline-block; margin: 10px 0;">
                        ${result.token_no}
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 10px; line-height: 1.4;">
                        Please copy or note down this <strong>Enquiry Token Number</strong> for all future follow-up references.
                    </p>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-file-invoice-dollar"></i> Create Provisional Order',
                cancelButtonText: '<i class="fa-solid fa-thumbs-up"></i> Done',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: 'var(--primary-accent)',
                customClass: {
                    popup: 'swal-premium-popup'
                }
            }).then((choice) => {
                const contact = document.getElementById('contact').value.trim();
                const name = document.getElementById('name').value.trim();
                const stateName = document.getElementById('state-search').value.trim();
                const districtName = document.getElementById('district-search').value.trim();
                const city = document.getElementById('city').value.trim();
                const remarks = document.getElementById('remark').value.trim();
                
                // Keep products info before clearing
                const passedProducts = [...selectedProducts];

                form.reset();
                selectedProducts = [];

                // Reset dynamic tables
                const addedProductsList = document.getElementById('added-products-list');
                if (addedProductsList) {
                    addedProductsList.innerHTML = `
                        <tr class="empty-product-row">
                            <td colspan="3" style="text-align: center; color: var(--text-light); padding: 1.5rem 0;">No products added to this enquiry yet. Please add at least one.</td>
                        </tr>
                    `;
                }

                if (stateDropdown) stateDropdown.reset();
                if (districtDropdown) {
                    districtDropdown.reset();
                    districtDropdown.disable();
                }
                if (productDropdown) productDropdown.reset();

                if (choice.isConfirmed) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const key = urlParams.get('k');
                    const params = new URLSearchParams({
                        phone: contact,
                        name: name,
                        state: stateName,
                        district: districtName,
                        city: city,
                        remarks: remarks,
                        products: JSON.stringify(passedProducts)
                    });
                    if (key) {
                        params.set('k', key);
                    }
                    window.location.href = '../krf-orderandsales/addNewProvisionalsaleorder.php?' + params.toString();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: result.message || 'Failed to register lead.',
                confirmButtonColor: 'var(--primary-accent)'
            });
        }
    } catch (e) {
        console.error('Submit error:', e);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'An unexpected network error occurred while submitting.',
            confirmButtonColor: 'var(--primary-accent)'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHTML;
    }
}

// Initialise page
document.addEventListener('DOMContentLoaded', () => {
    loadStates();
    loadProducts();
    setupContactClientSearch();
    setupMultiProductHandlers();
    document.getElementById('lead-form').addEventListener('submit', handleFormSubmit);
});
