<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentResponse;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DepartmentResponseSeeder extends Seeder
{
    /**
     * Seed FAQ responses for all departments.
     *
     * Each department receives 5 approved, active entries with
     * bilingual content and trigger keywords.
     */
    public function run(): void
    {
        // Resolve the admin user who will be the "created_by" for all seed data.
        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command->warn('No admin user found. Creating a default admin for seeding.');

            $admin = User::create([
                'name' => 'System Admin',
                'email' => 'admin@putrakop.test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'language_preference' => 'en',
            ]);
        }

        $departments = [
            'Insurance' => $this->insuranceEntries(),
            'Membership' => $this->membershipEntries(),
            'Finance' => $this->financeEntries(),
            'Tech Support' => $this->techSupportEntries(),
            'General Inquiry' => $this->generalInquiryEntries(),
        ];

        foreach ($departments as $deptName => $entries) {
            $department = Department::firstOrCreate(
                ['name_en' => $deptName],
                [
                    'name_bm' => $this->translateDepartmentName($deptName),
                    'is_active' => true,
                    'priority' => 0,
                ],
            );

            foreach ($entries as $entry) {
                DepartmentResponse::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'response_key' => $entry['response_key'],
                    ],
                    [
                        'content_en' => $entry['content_en'],
                        'content_bm' => $entry['content_bm'],
                        'trigger_keywords' => $entry['trigger_keywords'],
                        'priority' => $entry['priority'] ?? 0,
                        'is_active' => true,
                        'is_approved' => true,
                        'created_by' => $admin->id,
                    ],
                );
            }

            $this->command->info("  ✔ Seeded {$deptName} ({$department->id}) — " . count($entries) . " responses");
        }
    }

    // ─── Insurance ──────────────────────────────────────────────

    private function insuranceEntries(): array
    {
        return [
            [
                'response_key' => 'insurance_claim_process',
                'content_en' => 'To file an insurance claim, please visit our Claims Portal or contact our claims hotline at 1-800-XXX-XXXX. You will need your policy number, a valid ID, and supporting documents related to your claim.',
                'content_bm' => 'Untuk membuat tuntutan insurans, sila lawati Portal Tuntutan kami atau hubungi talian tuntutan kami di 1-800-XXX-XXXX. Anda memerlukan nombor polisi, ID yang sah, dan dokumen sokongan berkaitan tuntutan anda.',
                'trigger_keywords' => ['claim', 'insurance claim', 'file claim', 'tuntutan', 'tuntutan insurans'],
                'priority' => 10,
            ],
            [
                'response_key' => 'insurance_policy_renewal',
                'content_en' => 'Your insurance policy can be renewed 30 days before the expiry date. You will receive a renewal notice via email. You can renew online through the Member Portal or visit any PutraKop branch.',
                'content_bm' => 'Polisi insurans anda boleh diperbaharui 30 hari sebelum tarikh luput. Anda akan menerima notis pembaharuan melalui e-mel. Anda boleh memperbaharui dalam talian melalui Portal Ahli atau mengunjungi mana-mana cawangan PutraKop.',
                'trigger_keywords' => ['renewal', 'renew policy', 'policy expiry', 'memperbaharui', 'polisi luput'],
                'priority' => 9,
            ],
            [
                'response_key' => 'insurance_coverage_check',
                'content_en' => 'To check your current insurance coverage, log in to the Member Portal and navigate to My Policies. You can view your coverage details, limits, and exclusions. For specific questions, please contact your agent.',
                'content_bm' => 'Untuk menyemak liputan insurans semasa anda, log masuk ke Portal Ahli dan navigasi ke Polisi Saya. Anda boleh melihat butiran liputan, had, dan pengecualian anda. Untuk soalan spesifik, sila hubungi ejen anda.',
                'trigger_keywords' => ['coverage', 'what is covered', 'insurance details', 'liputan', 'perlindungan'],
                'priority' => 8,
            ],
            [
                'response_key' => 'insurance_premium_payment',
                'content_en' => 'Insurance premiums can be paid monthly, quarterly, or annually. Payment methods include online banking, credit/debit card, and auto-debit. Late payments may result in policy lapse after a 15-day grace period.',
                'content_bm' => 'Premium insurans boleh dibayar secara bulanan, suku tahunan, atau tahunan. Kaedah pembayaran termasuk perbankan dalam talian, kad kredit/debit, dan auto-debit. Lewat bayar boleh menyebabkan polisi terbatal selepas tempoh grasi 15 hari.',
                'trigger_keywords' => ['premium', 'payment', 'pay premium', 'premium payment', 'bayar', 'bayaran'],
                'priority' => 7,
            ],
            [
                'response_key' => 'insurance_cancel_policy',
                'content_en' => 'You may cancel your insurance policy at any time by submitting a written request. Cancellation within the cooling-off period (15 days from purchase) receives a full refund. After that, a pro-rata refund may apply.',
                'content_bm' => 'Anda boleh membatalkan polisi insurans anda pada bila-bila masa dengan mengemukakan permintaan bertulis. Pembatalan dalam tempoh penyejukan (15 hari dari pembelian) menerima bayaran penuh. Selepas itu, bayaran balik prorata mungkin dikenakan.',
                'trigger_keywords' => ['cancel', 'cancellation', 'terminate', 'batalkan', 'pembatalan'],
                'priority' => 6,
            ],
        ];
    }

    // ─── Membership ─────────────────────────────────────────────

    private function membershipEntries(): array
    {
        return [
            [
                'response_key' => 'membership_registration',
                'content_en' => 'To register as a PutraKop member, visit any branch with your IC/passport and a minimum deposit of RM10. Registration is also available online through our website. Membership is open to Malaysian citizens aged 18 and above.',
                'content_bm' => 'Untuk mendaftar sebagai ahli PutraKop, kunjungi mana-mana cawangan dengan IC/passport anda dan deposit minimum RM10. Pendaftaran juga tersedia dalam talian melalui laman web kami. Keahlian terbuka untuk warganegara Malaysia berumur 18 tahun ke atas.',
                'trigger_keywords' => ['register', 'membership', 'sign up', 'become member', 'pendaftaran', 'ahli'],
                'priority' => 10,
            ],
            [
                'response_key' => 'membership_benefits',
                'content_en' => 'As a PutraKop member, you enjoy benefits including: competitive dividend rates, access to loans, insurance coverage, financial education programs, and exclusive member discounts at partner merchants.',
                'content_bm' => 'Sebagai ahli PutraKop, anda menikmati faedah termasuk: kadar dividen kompetitif, akses kepada pinjaman, liputan insurans, program pendidikan kewangan, dan diskaun ahli eksklusif di merchant rakan kongsi.',
                'trigger_keywords' => ['benefits', 'member benefits', 'advantages', 'faedah', 'kelebihan ahli'],
                'priority' => 9,
            ],
            [
                'response_key' => 'membership_balance_check',
                'content_en' => 'To check your membership account balance, log in to the Member Portal or mobile app. You can also check your balance via SMS by sending "BAL" to 15XXX, or visit any branch for a printed statement.',
                'content_bm' => 'Untuk menyemak baka akaun keahlian anda, log masuk ke Portal Ahli atau aplikasi mudah alih. Anda juga boleh menyemak baki melalui SMS dengan menghantar "BAL" ke 15XXX, atau kunjungi mana-mana cawangan untuk penyata bercetak.',
                'trigger_keywords' => ['balance', 'account balance', 'check balance', 'baki', 'semak baki'],
                'priority' => 8,
            ],
            [
                'response_key' => 'membership_update_info',
                'content_en' => 'To update your personal information (address, phone number, email), please visit any branch with your IC and the new information. Changes can also be submitted through the Member Portal under Profile Settings.',
                'content_bm' => 'Untuk mengemaskini maklumat peribadi anda (alamat, nombor telefon, e-mel), sila kunjungi mana-mana cawangan dengan IC anda dan maklumat baru. Perubahan juga boleh disem melalui Portal Ahli di bawah Tetapan Profil.',
                'trigger_keywords' => ['update info', 'change address', 'update details', 'kemaskini', 'tukar alamat'],
                'priority' => 7,
            ],
            [
                'response_key' => 'membership_fee_structure',
                'content_en' => 'PutraKop membership requires a one-time registration fee of RM5 and a minimum share capital of RM10. Annual membership fee is RM10, automatically deducted from your account. There are no hidden charges.',
                'content_bm' => 'Keahlian PutraKop memerlukan yuran pendaftaran sekali sebanyak RM5 dan modal saham minimum RM10. Yuran keahlian tahunan ialah RM10, ditolak secara automatik dari akaun anda. Tiada caj tersembunyi.',
                'trigger_keywords' => ['fee', 'membership fee', 'cost', 'yuran', 'kos keahlian'],
                'priority' => 6,
            ],
        ];
    }

    // ─── Finance ────────────────────────────────────────────────

    private function financeEntries(): array
    {
        return [
            [
                'response_key' => 'finance_loan_application',
                'content_en' => 'To apply for a loan, you need at least 6 months of active membership, a valid ID, and proof of income. Applications can be submitted online or at any branch. Loan approval typically takes 3-5 business days.',
                'content_bm' => 'Untuk memohon pinjaman, anda memerlukan sekurang-kurangnya 6 bulan keahlian aktif, ID yang sah, dan bukti pendapatan. Permohonan boleh dihantar dalam talian atau di mana-mana cawangan. Kelulusan pinjaman biasanya mengambil masa 3-5 hari bekerja.',
                'trigger_keywords' => ['loan', 'apply loan', 'loan application', 'pinjaman', 'mohon pinjaman'],
                'priority' => 10,
            ],
            [
                'response_key' => 'finance_dividend_rate',
                'content_en' => 'PutraKop declares dividends annually based on net surplus. Current dividend rate is 5-7% per annum. Dividends are credited directly to your account. You may check the latest rate on our website or at any branch.',
                'content_bm' => 'PutraKop mengisytiharkan dividen secara tahunan berdasarkan lebihan bersih. Kadar dividen semasa ialah 5-7% setahun. Dividen dikreditkan terus ke akaun anda. Anda boleh menyemak kadar terkini di laman web kami atau di mana-mana cawangan.',
                'trigger_keywords' => ['dividend', 'dividend rate', 'return', 'dividen', 'kadar dividen'],
                'priority' => 9,
            ],
            [
                'response_key' => 'finance_transfer_funds',
                'content_en' => 'Fund transfers between PutraKop accounts are free and instant. External transfers to bank accounts may take 1-2 business days. You can transfer via the Member Portal, mobile app, or by visiting a branch.',
                'content_bm' => 'Pemindahan dana antara akaun PutraKop adalah percuma dan segera. Pemindahan luar ke akaun bank mungkin mengambil masa 1-2 hari bekerja. Anda boleh memindah melalui Portal Ahli, aplikasi mudah alih, atau dengan mengunjungi cawangan.',
                'trigger_keywords' => ['transfer', 'send money', 'fund transfer', 'pindah', 'pemindahan'],
                'priority' => 8,
            ],
            [
                'response_key' => 'finance_repayment_schedule',
                'content_en' => 'Your loan repayment schedule is available in the Member Portal under My Loans. You can view upcoming installments, remaining balance, and interest breakdown. Early repayment is allowed with no penalty.',
                'content_bm' => 'Jadual pembayaran balik pinjaman anda tersedia di Portal Ahli di bawah Pinjaman Saya. Anda boleh melihat ansuran akan datang, baki tertunggak, dan pecahan faedah. Pembayaran awal dibenarkan tanpa penalti.',
                'trigger_keywords' => ['repayment', 'installment', 'schedule', 'bayaran balik', 'ansuran'],
                'priority' => 7,
            ],
            [
                'response_key' => 'finance_statement_request',
                'content_en' => 'You can request a financial statement through the Member Portal or by emailing support@putrakop.com.my. Statements are generated within 2 business days. Monthly e-statements are automatically sent to your registered email.',
                'content_bm' => 'Anda boleh meminta penyata kewangan melalui Portal Ahli atau dengan menghantar e-mel ke support@putrakop.com.my. Penyata dijana dalam 2 hari bekerja. Penyata e-bulanan dihantar secara automatik ke e-mel anda yang didaftarkan.',
                'trigger_keywords' => ['statement', 'financial statement', 'account statement', 'penyata', 'penyata kewangan'],
                'priority' => 6,
            ],
        ];
    }

    // ─── Tech Support ───────────────────────────────────────────

    private function techSupportEntries(): array
    {
        return [
            [
                'response_key' => 'tech_login_trouble',
                'content_en' => 'If you are having trouble logging in, try resetting your password via the "Forgot Password" link. If the issue persists, clear your browser cache, try a different browser, or contact support with your registered email.',
                'content_bm' => 'Jika anda mengalami masalah log masuk, cuba tetapkan semula kata laluan anda melalui pautan "Lupa Kata Laluan". Jika masalah berterusan, kosongkan cache pelayar anda, cuba pelayar berbeza, atau hubungi sokongan dengan e-mel anda yang didaftarkan.',
                'trigger_keywords' => ['login', 'cannot login', 'log in problem', 'masuk', 'tidak boleh log masuk'],
                'priority' => 10,
            ],
            [
                'response_key' => 'tech_mobile_app_issue',
                'content_en' => 'For mobile app issues, first ensure you are running the latest version from the App Store or Google Play. Try restarting the app or your device. If problems continue, uninstall and reinstall the app, then log in again.',
                'content_bm' => 'Untuk masalah aplikasi mudah alih, pastikan anda menjalankan versi terkini dari App Store atau Google Play. Cuba mulakan semula aplikasi atau peranti anda. Jika masalah berteransom, nyahpasang dan pasang semula aplikasi, kemudian log masuk semula.',
                'trigger_keywords' => ['app', 'mobile app', 'application', 'aplikasi', 'aplikasi mudah alih'],
                'priority' => 9,
            ],
            [
                'response_key' => 'tech_password_reset',
                'content_en' => 'To reset your password: 1) Go to the login page, 2) Click "Forgot Password", 3) Enter your registered email, 4) Check your inbox for the reset link. The link expires in 60 minutes. Check your spam folder if you don\'t see it.',
                'content_bm' => 'Untuk menetapkan semula kata laluan: 1) Pergi ke halaman log masuk, 2) Klik "Lupa Kata Laluan", 3) Masukkan e-mel anda yang didaftarkan, 4) Semak peti masuk anda untuk pautan penetapan semula. Pautan tamat tempoh dalam 60 minit. Semak folder spam anda jika anda tidak melihatnya.',
                'trigger_keywords' => ['password reset', 'forgot password', 'reset password', 'lupa kata laluan'],
                'priority' => 8,
            ],
            [
                'response_key' => 'tech_website_slow',
                'content_en' => 'If the website is loading slowly, try: clearing your browser cache and cookies, disabling browser extensions, checking your internet connection, or trying a different browser. If the issue persists, it may be temporary — please try again later.',
                'content_bm' => 'Jika laman web dimuatkan dengan perlahan, cuba: kosongkan cache dan kuki pelayar anda, nyahaktifkan sambungan pelayar, semak sambungan internet anda, atau cuba pelayar berbeza. Jika masalah berterusan, ia mungkin sementara — sila cuba lagi kemudian.',
                'trigger_keywords' => ['slow', 'website slow', 'loading', 'perlahan', 'laman web lambat'],
                'priority' => 7,
            ],
            [
                'response_key' => 'tech_system_maintenance',
                'content_en' => 'Scheduled maintenance is typically performed on Sundays from 2:00 AM to 6:00 AM. During this time, some services may be temporarily unavailable. We announce maintenance windows at least 48 hours in advance via email and social media.',
                'content_bm' => 'Penyelenggaraan berjadual biasanya dilakukan pada hari Ahad dari pukul 2:00 PG hingga 6:00 PG. Semasa waktu ini, sesetengah perkhidmatan mungkin tidak tersedia buat sementara waktu. Kami mengumumkan tetingkap penyelenggaraan sekurang-kurangnya 48 jam lebih awal melalui e-mel dan media sosial.',
                'trigger_keywords' => ['maintenance', 'down', 'unavailable', 'penyelenggaraan', 'tidak tersedia'],
                'priority' => 6,
            ],
        ];
    }

    // ─── General Inquiry ────────────────────────────────────────

    private function generalInquiryEntries(): array
    {
        return [
            [
                'response_key' => 'general_branch_hours',
                'content_en' => 'PutraKop branches are open Monday to Friday, 9:00 AM to 5:00 PM, and Saturday, 9:00 AM to 1:00 PM. Branches are closed on Sundays and public holidays. Operating hours may vary during festive seasons.',
                'content_bm' => 'Cawangan PutraKop dibuka dari Isnin hingga Jumaat, 9:00 PG hingga 5:00 PTG, dan Sabtu, 9:00 PG hingga 1:00 PTG. Cawangan ditutup pada hari Ahad dan hari cuti umum. Waktu operasi mungkin berbeza semasa musim perayaan.',
                'trigger_keywords' => ['branch', 'hours', 'opening hours', 'cawangan', 'waktu operasi', 'jam buka'],
                'priority' => 10,
            ],
            [
                'response_key' => 'general_contact_info',
                'content_en' => 'You can reach PutraKop via: Phone: 1-800-XXX-XXXX (toll-free), Email: support@putrakop.com.my, WhatsApp: +60-1X-XXX-XXXX, or visit any branch. Our customer service operates Monday to Saturday, 8:00 AM to 8:00 PM.',
                'content_bm' => 'Anda boleh menghubungi PutraKop melalui: Telefon: 1-800-XXX-XXXX (bebas tol), E-mel: support@putrakop.com.my, WhatsApp: +60-1X-XXX-XXXX, atau kunjungi mana-mana cawangan. Perkhidmatan pelanggan kami beroperasi dari Isnin hingga Sabtu, 8:00 PG hingga 8:00 MLG.',
                'trigger_keywords' => ['contact', 'phone', 'email', 'hubungi', 'telefon', 'e-mel'],
                'priority' => 9,
            ],
            [
                'response_key' => 'general_feedback_complaint',
                'content_en' => 'We value your feedback! To submit feedback or a complaint, please email feedback@putrakop.com.my with the subject line and detailed description. You can also use the feedback form in the Member Portal. We respond within 3 business days.',
                'content_bm' => 'Kami menghargai maklum balas anda! Untuk menghantar maklum balas atau aduan, sila e-mel feedback@putrakop.com.my dengan baris subjek dan penerangan terperinci. Anda juga boleh menggunakan borang maklum balas di Portal Ahli. Kami membalas dalam 3 hari bekerja.',
                'trigger_keywords' => ['feedback', 'complaint', 'suggestion', 'maklum balas', 'aduan'],
                'priority' => 8,
            ],
            [
                'response_key' => 'general_privacy_policy',
                'content_en' => 'PutraKop takes your privacy seriously. Our full Privacy Policy is available at putrakop.com.my/privacy. We comply with PDPA (Malaysia) and do not share your personal data with third parties without consent.',
                'content_bm' => 'PutraKop mengambil serius privasi anda. Dasar Privasi penuh kami tersedia di putrakop.com.my/privacy. Kami mematuhi PDPA (Malaysia) dan tidak berkongsi data peribadi anda dengan pihak ketiga tanpa kebenaran.',
                'trigger_keywords' => ['privacy', 'data protection', 'personal data', 'privasi', 'perlindungan data'],
                'priority' => 7,
            ],
            [
                'response_key' => 'general_vacancy_info',
                'content_en' => 'For job opportunities at PutraKop, please visit our Careers page at putrakop.com.my/careers or email your resume to hr@putrakop.com.my. We post new openings regularly and welcome applications from qualified candidates.',
                'content_bm' => 'Untuk peluang kerjaya di PutraKop, sila lawati Halaman Kerjaya kami di putrakop.com.my/careers atau e-mel resume anda ke hr@putrakop.com.my. Kami memaparkan kekosongan baru secara berkala dan mengalu-alukan permohonan dari calon yang layak.',
                'trigger_keywords' => ['job', 'career', 'vacancy', 'vacancies', 'kerja', 'kekosongan', 'peluang kerjaya'],
                'priority' => 6,
            ],
        ];
    }

    /**
     * Map department English names to Malay equivalents for seeding.
     */
    private function translateDepartmentName(string $name): string
    {
        return match ($name) {
            'Insurance' => 'Insurans',
            'Membership' => 'Keahlian',
            'Finance' => 'Kewangan',
            'Tech Support' => 'Sokongan Teknikal',
            'General Inquiry' => 'Pertanyaan Am',
            default => $name,
        };
    }
}
