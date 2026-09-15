/**
 * Admin Campaign Fast Creator (Alpine Component)
 */
export function campaignCreateComponent() {
    const roomSlug = window.__DF_ROOM_SLUG__ || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
        menuTab: 'manual',
        menuApplyMode: 'replace',
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
            name: '',
            restaurant: '',
            deadline: '',
            payment_account_id: '',
            description: '',
            sponsor_name: '',
            sponsor_type: 'none',
            sponsor_description: '',
            max_budget: '',
            delivery_fee: '',
            discount: '',
            flat_price: '',
            status: 'active'
        },
        menuItems: [
            { name: 'Cà phê Phin Sữa Đá', price: 29000, category: 'Cà phê' },
            { name: 'Trà Sen Vàng', price: 45000, category: 'Trà' },
            { name: 'Freeze Trà Xanh', price: 55000, category: 'Freeze' }
        ],
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
            this.menuTab = 'manual';
        },

        applyMenuItems(items) {
            const normalized = (items || []).map(item => ({ name: item.name || '', price: parseInt(item.price, 10) || 0, category: item.category || 'Khác', sponsor_amount: parseInt(item.sponsor_amount, 10) || 0 }));
            this.menuItems = this.menuApplyMode === 'append' ? this.menuItems.concat(normalized) : normalized;
        },

        addSponsor() {
            this.sponsors.push({ user_id: '', type: 'per_item', amount: 0, description: '' });
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
            this.menuTab = 'manual';
            return;
            this.menuItems = (campaign.items || []).map(i => ({
                name: i.name,
                price: i.price,
                category: i.category || 'Món chung'
            }));
            this.menuTab = 'manual';
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
                    this.menuTab = 'manual';
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
                const res = await fetch(`/admin/${roomSlug}/crawler/preview`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ url: this.crawlerUrl })
                });
                const data = await res.json();
                if (data.items && data.items.length > 0) {
                    this.menuItems = data.items.map(item => ({
                        name: item.name,
                        price: item.price || 0,
                        category: item.category || 'Món Crawl'
                    }));
                    this.form.restaurant = data.restaurant_name || this.form.restaurant || 'Nhà hàng Online';
                    this.menuTab = 'manual';
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

            const payload = {
                name: this.form.name,
                restaurant: this.form.restaurant,
                deadline: this.form.deadline || null,
                payment_account_id: this.form.payment_account_id || null,
                description: this.form.description || null,
            sponsor_name: this.form.sponsor_name || null,
                sponsor_type: this.form.sponsor_type,
                sponsor_description: this.form.sponsor_description || null,
                max_budget: this.form.max_budget ? parseInt(this.form.max_budget, 10) : null,
                flat_price: this.form.flat_price ? parseInt(this.form.flat_price, 10) : null,
                sponsor_allocations: this.sponsors.filter(sponsor => sponsor.user_id).map(sponsor => ({
                    room_user_id: Number(sponsor.user_id),
                    type: sponsor.type,
                    amount: Number(sponsor.amount) || 0,
                    description: sponsor.description || null
                })),
                status: status
            };

            try {
                const res = await fetch(`/admin/${roomSlug}/campaigns`, {
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
                            await fetch(`/admin/${roomSlug}/campaigns/${campaignId}/items`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    name: item.name,
                                    base_price: parseInt(item.price, 10) || 0,
                                    sponsor_amount: parseInt(item.sponsor_amount, 10) || 0,
                                    category: item.category || null
                                })
                            });
                        }
                    }
                }

                window.location.href = `/admin/${roomSlug}/campaigns/${campaignId}`;
            } catch (e) {
                alert('Có lỗi xảy ra: ' + e.message);
            } finally {
                this.submitting = false;
            }
        },

        saveDraft() {
            this.submitForm('draft');
        },

        publishCampaign() {
            this.submitForm('active');
        }
    };
}

if (typeof window !== 'undefined') {
    window.campaignCreateComponent = campaignCreateComponent;
}
