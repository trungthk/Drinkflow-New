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
    const itemUrlTemplate = page?.dataset.itemUrlTemplate || '';
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
        newItem: { name: '', price: 0, category: 'Khác' },
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
            const normalized = (items || []).map(item => ({ name: item.name || '', price: parseInt(item.price, 10) || 0, category: item.category || 'Khác' }));
            this.menuItems = normalized;
        },

        addSponsor() {
            this.sponsors.push({ user_id: '', percentage: 0 });
        },

        removeSponsor(index) {
            this.sponsors.splice(index, 1);
        },

        addMenuItem() {
            this.menuItems.push({ name: '', price: 0, category: 'Món chung' });
        },

        openAddItemModal() {
            this.newItem = { name: '', price: 0, category: this.itemCategories[0] };
            this.showAddItemModal = true;
        },

        confirmAddItem() {
            if (!this.newItem.name.trim() || Number(this.newItem.price) < 0) return;
            this.applyMenuItems([this.newItem]);
            this.showAddItemModal = false;
        },

        removeMenuItem(index) {
            this.menuItems.splice(index, 1);
        },

        loadPreviousCampaign(campaign) {
            this.form.name = campaign.name + ' (Đợt mới)';
            this.form.restaurant = campaign.restaurant;
            this.applyMenuItems(campaign.items || []);
            this.menuTab = 'reuse';
        },

        loadSampleJson() {
            this.rawJson = JSON.stringify([
                { name: 'Trà Sữa Trân Châu Đường Đen', price: 45000, category: 'Trà Sữa' },
                { name: 'Trà Oolong Vải', price: 40000, category: 'Trà Trái Cây' },
                { name: 'Cà phê Muối Đặc Biệt', price: 30000, category: 'Cà Phê' }
            ], null, 2);
        },

        importJson() {
            try {
                const parsed = JSON.parse(this.rawJson);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    this.menuItems = parsed.map(item => ({
                        name: item.name || 'Món mới',
                        price: parseInt(item.price, 10) || 0,
                        category: item.category || 'Món chung'
                    }));
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
                        name: item.name,
                        price: item.base_price || item.price || 0,
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
                status: status
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

                if (campaignId && this.menuItems.length > 0) {
                    for (const item of this.menuItems) {
                        if (item.name && item.name.trim()) {
                            await fetch(itemUrlTemplate.replace('__CAMPAIGN__', campaignId), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    name: item.name,
                                    base_price: parseInt(item.price, 10) || 0,
                                    category: item.category || null
                                })
                            });
                        }
                    }
                }

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
