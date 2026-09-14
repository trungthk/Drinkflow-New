<?php

namespace Tests\Feature;

use App\Models\ContactInquiry;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function test_contact_page_returns_successful_response(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('Liên hệ với Đội ngũ DrinkFlow');
        $response->assertSee('Gửi tin nhắn hoặc yêu cầu hỗ trợ');
        $response->assertSee('id="full_name"', false);
        $response->assertSee('id="work_email"', false);
        $response->assertSee('id="phone"', false);
        $response->assertSee('id="company"', false);
        $response->assertSee('id="topic"', false);
        $response->assertSee('id="message"', false);
        $response->assertSee('id="captcha"', false);
        $response->assertSee('id="refresh-captcha-btn"', false);
        $response->assertSee('0377300950');
        $response->assertSee('href="tel:0377300950"', false);
        $response->assertSee('drinkflowsupport@gmail.com');
        $response->assertSee('href="mailto:drinkflowsupport@gmail.com"', false);
        // Ensure removed items are no longer present
        $response->assertDontSee('it-security@company.com');
        $response->assertDontSee('#drinkflow-support');
        $response->assertDontSee('14:00 - 16:30');
        // Ensure all 4 FAQs are displayed
        $response->assertSee(__('contact.faq.q1'));
        $response->assertSee(__('contact.faq.q2'));
        $response->assertSee(__('contact.faq.q3'));
        $response->assertSee(__('contact.faq.q4'));

        // Captcha image endpoint works without 500 error
        $captchaRes = $this->get('/captcha/contact');
        $captchaRes->assertStatus(200);
    }

    public function test_contact_form_submission_requires_captcha_by_default(): void
    {
        Config::set('captcha.disable', false);

        $response = $this->from('/contact')->post('/contact', [
            'full_name' => 'Nguyen Van A',
            'work_email' => 'vana@company.com',
            'phone' => '0912345678',
            'company' => 'Engineering Dept',
            'topic' => 'deploy',
            'message' => 'Toi muon trien khai DrinkFlow cho chi nhanh Da Nang.',
            // omitted captcha
        ]);

        $response->assertRedirect('/contact');
        $response->assertSessionHasErrors(['captcha']);
    }

    public function test_contact_form_submission_fails_with_invalid_captcha(): void
    {
        Config::set('captcha.disable', false);

        $response = $this->from('/contact')->post('/contact', [
            'full_name' => 'Nguyen Van A',
            'work_email' => 'vana@company.com',
            'phone' => '0912345678',
            'company' => 'Engineering Dept',
            'topic' => 'deploy',
            'message' => 'Toi muon trien khai DrinkFlow cho chi nhanh Da Nang.',
            'captcha' => 'WRONG_CODE',
        ]);

        $response->assertRedirect('/contact');
        $response->assertSessionHasErrors(['captcha']);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        Config::set('captcha.disable', true);

        $response = $this->from('/contact')->post('/contact', [
            'full_name' => '',
            'work_email' => 'invalid-email',
            'phone' => '',
            'company' => '',
            'topic' => 'invalid_topic',
            'message' => 'short',
        ]);

        $response->assertRedirect('/contact');
        $response->assertSessionHasErrors(['full_name', 'work_email', 'phone', 'company', 'topic', 'message']);
    }

    public function test_successful_contact_form_submission_persists_inquiry_and_redirects(): void
    {
        Config::set('captcha.disable', true);

        $response = $this->from('/contact')->post('/contact', [
            'full_name' => 'Le Tuan Trung',
            'work_email' => 'trung.lt@drinkflow.corp',
            'phone' => '0987654321',
            'company' => 'Tech Division',
            'topic' => 'vietqr',
            'message' => 'Yeu cau kiem tra doi soat giao dich VietQR cho ca dat nuoc buoi chieu ngay hom nay.',
        ]);

        $response->assertRedirect('/contact');
        $response->assertSessionHas('success_ticket');

        $ticket = session('success_ticket');
        $this->assertStringStartsWith('#DF-', $ticket);

        $this->assertDatabaseHas('contact_inquiries', [
            'ticket_code' => $ticket,
            'full_name' => 'Le Tuan Trung',
            'work_email' => 'trung.lt@drinkflow.corp',
            'phone' => '0987654321',
            'company' => 'Tech Division',
            'topic' => 'vietqr',
            'status' => 'pending',
        ]);
    }

    public function test_contact_submission_returns_json_when_requested(): void
    {
        Config::set('captcha.disable', true);

        $response = $this->postJson('/contact', [
            'full_name' => 'Tran Thi B',
            'work_email' => 'b.tran@corp.com',
            'phone' => '0909000111',
            'company' => 'Finance Dept',
            'topic' => 'merchant',
            'message' => 'Toi muon dang ky chuoi tra sua Gong Cha lam doi tac Merchant cua DrinkFlow.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ticket_code',
            'message',
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_contact_page_renders_in_english_and_japanese(): void
    {
        // Switch to English
        $this->get('/lang/en')->assertRedirect();
        $responseEn = $this->withSession(['locale' => 'en'])->get('/contact');
        $responseEn->assertStatus(200);
        $responseEn->assertSee('Contact the DrinkFlow Team');
        $responseEn->assertSee('Send a Message or Support Request');
        $responseEn->assertSee('Security Code');

        // Switch to Japanese
        $this->get('/lang/ja')->assertRedirect();
        $responseJa = $this->withSession(['locale' => 'ja'])->get('/contact');
        $responseJa->assertStatus(200);
        $responseJa->assertSee('DrinkFlow チームへのお問い合わせ');
        $responseJa->assertSee('メッセージ・サポート申請の送信');
        $responseJa->assertSee('認証コード');
    }

    public function test_contact_submission_rate_limiting_by_ip(): void
    {
        Config::set('captcha.disable', true);
        $testIp = '198.51.100.99';

        // Submit 5 requests from testIp within the allowed per-minute limit
        for ($i = 1; $i <= 5; $i++) {
            $res = $this->withServerVariables(['REMOTE_ADDR' => $testIp])->postJson('/contact', [
                'full_name' => "User {$i}",
                'work_email' => "user{$i}@company.com",
                'phone' => '0987654321',
                'company' => 'Tech Corp',
                'topic' => 'feedback',
                'message' => 'Test message for rate limiting inquiry submission.',
            ]);
            $res->assertStatus(200);
        }

        // 6th request from the same testIp must be throttled with HTTP 429
        $throttled = $this->withServerVariables(['REMOTE_ADDR' => $testIp])->postJson('/contact', [
            'full_name' => 'User 6',
            'work_email' => 'user6@company.com',
            'phone' => '0987654321',
            'company' => 'Tech Corp',
            'topic' => 'feedback',
            'message' => 'Should be rate limited.',
        ]);

        $throttled->assertStatus(429);
    }
}
