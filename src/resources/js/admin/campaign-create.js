/**
 * Admin Campaign Fast Creator (Alpine Component)
 */
export function campaignCreateComponent(defaults = {}) {
    const page = document.querySelector('#campaign-create-page');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const getRoomSlug = () => document.body?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const budgetErrorTemplate = page?.dataset.budgetError || 'Campaign budget exceeds :limit.';
    const sponsorPercentageError = page?.dataset.sponsorPercentageError || 'The total sponsorship percentage must equal 100%.';
    const storeUrl = page?.dataset.storeUrl || '';
    const imageUploadUrl = page?.dataset.imageUploadUrl || '';
    const campaignSettings = {
        name: defaults.name || '',
        max_budget: Number(defaults.max_budget) || 0,
        payment_account_id: String(defaults.payment_account_id || '')
    };

    return {
        campaignSettings,
        menuTab: 'reuse',
        showConfirmModal: false,
        pendingStatus: 'active',
        showAddItemModal: false,
        itemCategories: ['Cà phê', 'Trà', 'Trà sữa', 'Nước ép', 'Đồ ăn', 'Khác'],
        newItem: { name: '', price: 0, category: 'Khác', description: '', image_url: '', toppings: [], options: [] },
        imageUploading: false,
        sponsors: [],
        submitting: false,
        crawlerUrl: '',
        crawlerLoading: false,
        crawlerMessage: '',
        rawJson: '',
        form: {
            name: campaignSettings.name,
            restaurant: '',
            deadline: '',
            payment_account_id: campaignSettings.payment_account_id,
            description: '',
            sponsor_type: 'none',
            sponsor_description: '',
            max_budget: campaignSettings.max_budget,
            delivery_fee: '',
            discount: '',
            flat_price: '',
            status: 'active'
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
            this.form.name = this.form.name || this.campaignSettings.name;
            this.form.max_budget = this.form.max_budget || this.campaignSettings.max_budget;
            this.form.payment_account_id = this.form.payment_account_id || this.campaignSettings.payment_account_id;
            this.setDeadlineMinutes(60);
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
        },

        normalizeMenuItem(item) {
            return {
                name: item.name || '',
                price: parseInt(item.price ?? item.base_price, 10) || 0,
                category: item.category || 'Khác',
                description: item.description || '',
                image_url: item.image_url || '',
                toppings: (item.toppings || []).map(topping => ({
                    name: topping.name || '',
                    price: parseInt(topping.price, 10) || 0
                })),
                options: (item.options || item.sizes || []).map(option => ({
                    name: option.name || '',
                    price_delta: parseInt(option.price_delta, 10) || 0
                }))
            };
        },

        addSponsor() {
            this.sponsors.push({ user_id: '', percentage: 0 });
        },

        removeSponsor(index) {
            this.sponsors.splice(index, 1);
        },

        addMenuItem() {
            this.menuItems.push(this.normalizeMenuItem({ category: 'Món chung' }));
        },

        openAddItemModal() {
            this.newItem = this.normalizeMenuItem({ category: this.itemCategories[0] });
            this.showAddItemModal = true;
        },

        confirmAddItem() {
            if (!this.newItem.name.trim() || Number(this.newItem.price) < 0) return;
            this.menuItems.push(this.normalizeMenuItem(this.newItem));
            this.showAddItemModal = false;
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
        },

        loadPreviousCampaign(campaign) {
            this.form.name = campaign.name + ' (Đợt mới)';
            this.form.restaurant = campaign.restaurant;
            this.form.description = campaign.description || '';
            this.form.max_budget = campaign.max_budget ?? this.form.max_budget;
            this.form.flat_price = campaign.flat_price || '';
            this.form.payment_account_id = campaign.payment_account_id ? String(campaign.payment_account_id) : this.form.payment_account_id;
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
                        category: item.category || 'Món Crawl'
                    }));
                    this.form.restaurant = data.restaurant_name || this.form.restaurant || 'Nhà hàng Online';
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
            this.form.status = status;
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
                status: status,
                items: this.menuItems.filter(item => item.name && item.name.trim()).map(item => ({
                    name: item.name.trim(),
                    category: item.category || null,
                    description: item.description || null,
                    image_url: item.image_url || null,
                    price: parseInt(item.price, 10) || 0,
                    toppings: (item.toppings || []).filter(topping => topping.name && topping.name.trim()).map(topping => ({
                        name: topping.name.trim(),
                        price: parseInt(topping.price, 10) || 0
                    })),
                    options: (item.options || []).filter(option => option.name && option.name.trim()).map(option => ({
                        name: option.name.trim(),
                        price_delta: parseInt(option.price_delta, 10) || 0
                    }))
                }))
            };

            try {
                const res = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message || Object.values(err.errors || {})[0] || 'Lỗi tạo chiến dịch');
                }

                const json = await res.json();
                const campaignId = json.data?.id || json.id;

                window.location.href = `/admin/${getRoomSlug()}/campaigns/${campaignId}`;
            } catch (e) {
                alert('Có lỗi xảy ra: ' + e.message);
            } finally {
                this.submitting = false;
            }
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
