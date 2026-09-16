/**
 * Admin Campaign Creator & Editor (Alpine Component)
 */
export function campaignCreateComponent(defaults = {}, availableRoomUsers = [], initialCampaign = null) {
    const page = document.querySelector('#campaign-create-page, #campaign-edit-page');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const getRoomSlug = () => document.body?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const budgetErrorTemplate = page?.dataset.budgetError || 'Campaign budget exceeds :limit.';
    const sponsorPercentageError = page?.dataset.sponsorPercentageError || 'The total sponsorship percentage must equal 100%.';
    const storeUrl = page?.dataset.storeUrl || page?.dataset.submitUrl || '';
    const submitMethod = page?.dataset.submitMethod || (initialCampaign ? 'PATCH' : 'POST');
    const isEditMode = Boolean(initialCampaign || page?.dataset.isEdit === 'true');
    const imageUploadUrl = page?.dataset.imageUploadUrl || '';
    const defaultMenuCategory = page?.dataset.defaultMenuCategory || 'General items';
    const crawlerMenuCategory = page?.dataset.crawlerMenuCategory || 'Crawled items';
    const onlineRestaurantName = page?.dataset.onlineRestaurantName || 'Online restaurant';
    const normalizeSearch = value => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .trim();
    const roomUsers = availableRoomUsers.map(user => ({
        ...user,
        id: String(user.id),
        label: `${user.name || ''} (${user.user_code || ''})`,
        search: normalizeSearch(`${user.name || ''} ${user.user_code || ''}`)
    }));
    const campaignSettings = {
        name: defaults.name || '',
        max_budget: Number(defaults.max_budget) || 0,
        payment_account_id: String(defaults.payment_account_id || '')
    };

    return {
        campaignSettings,
        isEditMode,
        menuTab: initialCampaign ? 'reuse' : 'reuse',
        showConfirmModal: false,
        pendingStatus: 'active',
        showAddItemModal: false,
        editingItemIndex: null,
        itemModalTab: 'basic',
        itemSubmitting: false,
        menuView: 'all',
        selectedCategory: '',
        menuSearchInput: '',
        menuSearch: '',
        menuSearchTimer: null,
        itemCategories: ['Cà phê', 'Trà', 'Trà sữa', 'Nước ép', 'Đồ ăn', 'Khác'],
        newItem: { id: null, name: '', price: 0, category: 'Khác', description: '', image_url: '', toppings: [], options: [] },
        imageUploading: false,
        selectedImageFileName: '',
        sponsors: [],
        roomUsers,
        submitting: false,
        crawlerUrl: '',
        crawlerLoading: false,
        crawlerMessage: '',
        rawJson: '',
        form: {
            name: initialCampaign?.name || campaignSettings.name,
            restaurant: initialCampaign?.restaurant || '',
            deadline: initialCampaign?.deadline || '',
            payment_account_id: initialCampaign?.payment_account_id ? String(initialCampaign.payment_account_id) : campaignSettings.payment_account_id,
            description: initialCampaign?.description || '',
            sponsor_type: initialCampaign?.sponsor_type || 'none',
            sponsor_description: initialCampaign?.sponsor_description || '',
            max_budget: initialCampaign?.max_budget ?? campaignSettings.max_budget,
            delivery_fee: initialCampaign?.delivery_fee ?? '',
            discount: initialCampaign?.discount ?? '',
            flat_price: initialCampaign?.flat_price ?? '',
            status: initialCampaign?.status || 'active'
        },
        menuItems: [],
        presets: {
            highlands: {
                name: 'Highlands Coffee - Giờ Giải Lao',
                restaurant: 'Highlands Coffee',
                items: [
                    { name: 'Phin Sữa Đá Size M', price: 35000, category: 'Cà Phê Phin' },
                    { name: 'Trà Sen Vàng Củ Năng', price: 49000, category: 'Trà Đặc Biệt' },
                    { name: 'Freeze Trà Xanh Thạch', price: 55000, category: 'Freeze' },
                    { name: 'Bánh Mì Thịt Nướng', price: 25000, category: 'Bánh Mì' }
                ]
            },
            phuclong: {
                name: 'Phúc Long Tea & Coffee',
                restaurant: 'Phúc Long Tea',
                items: [
                    { name: 'Trà Sữa Phúc Long Size L', price: 55000, category: 'Trà Sữa' },
                    { name: 'Trà Đào Cam Sả', price: 50000, category: 'Trà Trái Cây' },
                    { name: 'Trà Ô Long Sữa', price: 50000, category: 'Trà Sữa' }
                ]
            },
            gongcha: {
                name: 'Gong Cha Trà Sữa Thượng Hạng',
                restaurant: 'Gong Cha',
                items: [
                    { name: 'Trà Sữa Trân Châu Hoàng Kim', price: 55000, category: 'Trà Sữa' },
                    { name: 'Alisan Milk Foam', price: 52000, category: 'Milk Foam' },
                    { name: 'Trà Đen Macchiato', price: 48000, category: 'Macchiato' }
                ]
            },
            tocotoco: {
                name: 'TocoToco Bubble Tea',
                restaurant: 'TocoToco',
                items: [
                    { name: 'Trà Sữa Trân Châu Sợi', price: 38000, category: 'Trà Sữa' },
                    { name: 'Trà Sữa Ba Anh Em', price: 45000, category: 'Trà Sữa' },
                    { name: 'Trà Xoài Bưởi Hồng', price: 42000, category: 'Trà Trái Cây' }
                ]
            },
            starbucks: {
                name: 'Starbucks Coffee Break',
                restaurant: 'Starbucks Vietnam',
                items: [
                    { name: 'Caramel Macchiato Grande', price: 85000, category: 'Espresso' },
                    { name: 'Green Tea Cream Frappuccino', price: 95000, category: 'Frappuccino' },
                    { name: 'Cold Brew Nitro', price: 90000, category: 'Cold Brew' }
                ]
            },
            comtam: {
                name: 'Cơm Tấm Phúc Lộc Gia - Bữa Trưa',
                restaurant: 'Cơm Tấm Phúc Lộc Gia',
                items: [
                    { name: 'Cơm Sườn Nướng Mỡ Hành', price: 45000, category: 'Cơm Tấm' },
                    { name: 'Cơm Sườn Bì Chả Đặc Biệt', price: 60000, category: 'Cơm Tấm' },
                    { name: 'Canh Rong Biển Thịt Bằm', price: 15000, category: 'Canh' }
                ]
            }
        },

        init() {
            if (initialCampaign) {
                this.form.name = initialCampaign.name || '';
                this.form.restaurant = initialCampaign.restaurant || '';
                this.form.deadline = initialCampaign.deadline || '';
                this.form.payment_account_id = initialCampaign.payment_account_id ? String(initialCampaign.payment_account_id) : '';
                this.form.description = initialCampaign.description || '';
                this.form.sponsor_type = ['none', 'full'].includes(initialCampaign.sponsor_type) ? initialCampaign.sponsor_type : 'none';
                this.form.sponsor_description = initialCampaign.sponsor_description || '';
                this.form.max_budget = initialCampaign.max_budget ?? this.campaignSettings.max_budget;
                this.form.flat_price = initialCampaign.flat_price ?? '';
                this.form.status = initialCampaign.status || 'active';
                this.sponsors = (initialCampaign.sponsor_allocations || []).map(alloc => ({
                    user_id: String(alloc.room_user_id || ''),
                    percentage: Number(alloc.percentage) || 0,
                    search: this.roomUsers.find(u => u.id === String(alloc.room_user_id || ''))?.label || '',
                    open: false
                }));
                this.applyMenuItems(initialCampaign.items || []);
            } else {
                this.form.name = this.form.name || this.campaignSettings.name;
                this.form.max_budget = this.form.max_budget || this.campaignSettings.max_budget;
                this.form.payment_account_id = this.form.payment_account_id || this.campaignSettings.payment_account_id;
                this.setDeadlineMinutes(60);
            }
        },

        setDeadlineMinutes(mins) {
            const d = new Date(Date.now() + mins * 60000);
            const pad = n => n.toString().padStart(2, '0');
            this.form.deadline = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },

        applyPreset(key) {
            const p = this.presets[key];
            if (!p) return;
            this.form.name = p.name;
            this.form.restaurant = p.restaurant;
            this.applyMenuItems(p.items);
            this.menuTab = 'reuse';
        },

        applyMenuItems(items) {
            this.menuItems = (items || []).map(item => this.normalizeMenuItem(item));
            this.syncSelectedCategory();
        },

        normalizeMenuItem(item) {
            return {
                id: item.id || null,
                name: item.name || '',
                price: parseInt(item.price ?? item.base_price, 10) || 0,
                category: item.category || 'Khác',
                description: item.description || '',
                image_url: item.image_url || '',
                toppings: (item.toppings || []).map(topping => ({
                    id: topping.id || null,
                    name: topping.name || '',
                    price: parseInt(topping.price, 10) || 0
                })),
                options: (item.options || item.sizes || []).map(option => ({
                    id: option.id || null,
                    name: option.name || '',
                    price_delta: parseInt(option.price_delta, 10) || 0
                }))
            };
        },

        addSponsor() {
            this.sponsors.push({ user_id: '', percentage: 0, search: '', open: false });
        },

        removeSponsor(index) {
            this.sponsors.splice(index, 1);
        },

        clampSponsorPercentage(sponsor) {
            if (String(sponsor.percentage) === '') return;
            const percentage = Number(sponsor.percentage);
            sponsor.percentage = Number.isFinite(percentage)
                ? Math.min(100, Math.max(0, percentage))
                : 0;
        },

        filteredSponsorUsers(query) {
            const needle = normalizeSearch(query);
            return this.roomUsers
                .filter(user => !needle || user.search.includes(needle))
                .slice(0, 12);
        },

        selectSponsor(sponsor, user) {
            sponsor.user_id = user.id;
            sponsor.search = user.label;
            sponsor.open = false;
        },

        addMenuItem() {
            this.menuItems.push(this.normalizeMenuItem({ category: defaultMenuCategory }));
        },

        openAddItemModal() {
            this.editingItemIndex = null;
            this.itemModalTab = 'basic';
            this.selectedImageFileName = '';
            this.newItem = this.normalizeMenuItem({ category: this.itemCategories[0] });
            this.showAddItemModal = true;
        },

        openEditItemModal(index) {
            if (!this.menuItems[index]) return;
            this.editingItemIndex = index;
            this.itemModalTab = 'basic';
            this.selectedImageFileName = '';
            this.newItem = this.normalizeMenuItem(this.menuItems[index]);
            this.showAddItemModal = true;
        },

        async confirmAddItem() {
            if (!this.newItem.name.trim() || Number(this.newItem.price) < 0) return;
            this.itemSubmitting = true;
            try {
                await new Promise(resolve => window.setTimeout(resolve, 150));
                const normalizedItem = this.normalizeMenuItem(this.newItem);
                if (this.editingItemIndex === null) {
                    this.menuItems.push(normalizedItem);
                } else {
                    this.menuItems.splice(this.editingItemIndex, 1, normalizedItem);
                }
                this.syncSelectedCategory();
                this.showAddItemModal = false;
                this.editingItemIndex = null;
            } finally {
                this.itemSubmitting = false;
            }
        },

        setMenuView(view) {
            this.menuView = view;
            if (view === 'category' && !this.menuCategories.includes(this.selectedCategory)) {
                this.selectedCategory = this.menuCategories[0] || '';
            }
        },

        updateMenuSearch(value) {
            this.menuSearchInput = value;
            window.clearTimeout(this.menuSearchTimer);
            this.menuSearchTimer = window.setTimeout(() => {
                this.menuSearch = normalizeSearch(value);
            }, 300);
        },

        get menuCategories() {
            return [...new Set(this.menuItems.map(item => item.category || 'Khác'))]
                .sort((left, right) => left.localeCompare(right, 'vi'));
        },

        syncSelectedCategory() {
            if (this.menuView === 'category' && !this.menuCategories.includes(this.selectedCategory)) {
                this.selectedCategory = this.menuCategories[0] || '';
            }
        },

        categoryItemCount(category) {
            return this.menuItems.filter(item => (item.category || 'Khác') === category).length;
        },

        get visibleMenuItems() {
            return this.menuItems
                .map((item, index) => ({ item, index }))
                .filter(({ item }) => !this.menuSearch || normalizeSearch(item.name).includes(this.menuSearch))
                .filter(({ item }) => this.menuView !== 'category' || (item.category || 'Khác') === this.selectedCategory);
        },

        addTopping(item) {
            item.toppings.push({ name: '', price: 0 });
        },

        addOption(item) {
            item.options.push({ name: '', price_delta: 0 });
        },

        async uploadManualImage(event) {
            const file = event.target.files?.[0];
            if (!file || !imageUploadUrl) return;

            this.selectedImageFileName = file.name;

            this.imageUploading = true;
            const body = new FormData();
            body.append('image', file);

            try {
                const response = await fetch(imageUploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body
                });
                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || Object.values(payload.errors || {})[0] || 'Không thể tải ảnh lên.');
                }
                this.newItem.image_url = payload.data?.url || '';
            } catch (error) {
                alert(error.message);
            } finally {
                this.imageUploading = false;
                event.target.value = '';
            }
        },

        removeMenuItem(index) {
            this.menuItems.splice(index, 1);
            this.syncSelectedCategory();
        },

        loadPreviousCampaign(campaign) {
            this.form.name = campaign.name + ' (Đợt mới)';
            this.form.restaurant = campaign.restaurant;
            this.form.description = campaign.description || '';
            const previousMaxBudget = Number(campaign.max_budget) || 0;
            this.form.max_budget = this.campaignSettings.max_budget > 0
                ? Math.min(previousMaxBudget, this.campaignSettings.max_budget)
                : previousMaxBudget;
            this.form.flat_price = campaign.flat_price || '';
            this.form.payment_account_id = campaign.payment_account_id ? String(campaign.payment_account_id) : this.form.payment_account_id;
            this.form.sponsor_type = ['none', 'full'].includes(campaign.sponsor_type) ? campaign.sponsor_type : 'none';
            this.form.sponsor_description = campaign.sponsor_description || '';
            this.sponsors = (campaign.sponsor_allocations || []).map(allocation => ({
                user_id: String(allocation.room_user_id || ''),
                percentage: Number(allocation.percentage) || 0,
                search: this.roomUsers.find(user => user.id === String(allocation.room_user_id || ''))?.label || '',
                open: false
            }));
            this.applyMenuItems(campaign.items || []);
            this.menuTab = 'reuse';
        },

        loadSampleJson() {
            this.rawJson = JSON.stringify([
                {
                    category: 'Trà sữa',
                    name: 'Trà sữa trân châu đường đen',
                    price: 45000,
                    image_url: 'https://example.com/images/tra-sua-tran-chau.jpg',
                    description: 'Trà sữa kèm trân châu đường đen.',
                    toppings: [
                        { name: 'Trân châu trắng', price: 7000 },
                        { name: 'Pudding trứng', price: 10000 }
                    ],
                    options: [
                        { name: 'Size M', price_delta: 0 },
                        { name: 'Size L', price_delta: 10000 }
                    ]
                },
                {
                    category: 'Cà phê',
                    name: 'Cà phê muối',
                    price: 30000,
                    image_url: 'https://example.com/images/ca-phe-muoi.jpg',
                    toppings: [],
                    options: []
                }
            ], null, 2);
        },

        importJson() {
            try {
                const parsed = JSON.parse(this.rawJson);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    this.menuItems = parsed.map(item => this.normalizeMenuItem(item));
                    this.menuTab = 'json';
                    alert(`Đã nạp thành công ${this.menuItems.length} món từ JSON!`);
                } else {
                    alert('JSON phải là một mảng danh sách các món ăn.');
                }
            } catch (e) {
                alert('Lỗi định dạng JSON: ' + e.message);
            }
        },

        async previewCrawler() {
            if (!this.crawlerUrl) return;
            this.crawlerLoading = true;
            this.crawlerMessage = 'Đang kết nối crawler tới ' + this.crawlerUrl + '...';

            try {
                const roomSlug = getRoomSlug();
                if (!roomSlug) {
                    throw new Error('Không xác định được phòng hiện tại.');
                }

                const res = await fetch(`/admin/${roomSlug}/crawler/preview`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ url: this.crawlerUrl })
                });
                const response = await res.json();
                if (!res.ok) {
                    throw new Error(response.message || 'Không thể bóc tách menu từ liên kết này.');
                }
                const data = response.data || response;
                if (data.items && data.items.length > 0) {
                    this.menuItems = data.items.map(item => ({
                        ...this.normalizeMenuItem(item),
                        category: item.category || crawlerMenuCategory
                    }));
                    this.form.restaurant = data.restaurant_name || this.form.restaurant || onlineRestaurantName;
                    this.menuTab = 'crawler';
                    this.crawlerMessage = `Bóc tách thành công ${data.items.length} món từ quán!`;
                } else {
                    this.crawlerMessage = 'Không tìm thấy món hoặc link không được hỗ trợ trực tiếp. Hệ thống đã nạp mẫu giả lập.';
                }
            } catch (e) {
                this.crawlerMessage = 'Lỗi kết nối bóc tách: ' + e.message;
            } finally {
                this.crawlerLoading = false;
            }
        },

        formatVND(num) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num || 0);
        },

        async submitForm(status) {
            if (!this.form.name || !this.form.restaurant) {
                alert('Vui lòng nhập Tên Chiến Dịch và Nhà Hàng.');
                return;
            }

            this.submitting = true;
            if (status) {
                this.form.status = status;
            }
            const maxBudget = Number(this.form.max_budget) || 0;
            if (this.campaignSettings.max_budget > 0 && maxBudget > this.campaignSettings.max_budget) {
                alert(budgetErrorTemplate.replace(':limit', this.formatVND(this.campaignSettings.max_budget)));
                this.submitting = false;
                return;
            }
            if (this.form.sponsor_type === 'full') {
                const percentageTotal = this.sponsors.reduce((total, sponsor) => total + (Number(sponsor.percentage) || 0), 0);
                if (this.sponsors.length === 0 || Math.abs(percentageTotal - 100) > 0.01) {
                    alert(sponsorPercentageError);
                    this.submitting = false;
                    return;
                }
            }

            const payload = {
                name: this.form.name,
                restaurant: this.form.restaurant,
                deadline: this.form.deadline || null,
                payment_account_id: this.form.payment_account_id || null,
                description: this.form.description || null,
                sponsor_type: this.form.sponsor_type,
                sponsor_description: this.form.sponsor_description || null,
                max_budget: this.form.max_budget ? parseInt(this.form.max_budget, 10) : null,
                flat_price: this.form.flat_price ? parseInt(this.form.flat_price, 10) : null,
                sponsor_allocations: this.form.sponsor_type === 'full' ? this.sponsors.filter(sponsor => sponsor.user_id).map(sponsor => ({
                        room_user_id: Number(sponsor.user_id),
                        percentage: Number(sponsor.percentage) || 0
                    })) : [],
                status: this.form.status,
                items: this.menuItems.filter(item => item.name && item.name.trim()).map(item => ({
                    id: item.id || null,
                    name: item.name.trim(),
                    category: item.category || null,
                    description: item.description || null,
                    image_url: item.image_url || null,
                    price: parseInt(item.price, 10) || 0,
                    toppings: (item.toppings || []).filter(topping => topping.name && topping.name.trim()).map(topping => ({
                        id: topping.id || null,
                        name: topping.name.trim(),
                        price: parseInt(topping.price, 10) || 0
                    })),
                    options: (item.options || []).filter(option => option.name && option.name.trim()).map(option => ({
                        id: option.id || null,
                        name: option.name.trim(),
                        price_delta: parseInt(option.price_delta, 10) || 0
                    }))
                }))
            };

            try {
                const res = await fetch(storeUrl, {
                    method: submitMethod,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message || Object.values(err.errors || {})[0] || (this.isEditMode ? 'Lỗi cập nhật chiến dịch' : 'Lỗi tạo chiến dịch'));
                }

                const json = await res.json();
                const campaignId = json.data?.id || json.id;

                if (this.isEditMode) {
                    window.location.href = `/admin/${getRoomSlug()}/campaigns/${campaignId}?view=detail`;
                } else {
                    window.location.href = `/admin/${getRoomSlug()}/campaigns/${campaignId}`;
                }
            } catch (e) {
                alert('Có lỗi xảy ra: ' + e.message);
            } finally {
                this.submitting = false;
            }
        },

        saveChanges() {
            this.submitForm(this.form.status);
        },

        openPublishConfirmation() {
            this.pendingStatus = 'active';
            this.showConfirmModal = true;
        },

        confirmPublish() {
            this.showConfirmModal = false;
            this.submitForm(this.pendingStatus);
        },

        saveDraft() {
            this.submitForm('draft');
        },

        publishCampaign() {
            this.openPublishConfirmation();
        }
    };
}

if (typeof window !== 'undefined') {
    window.campaignCreateComponent = campaignCreateComponent;
}
