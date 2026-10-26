<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerInformationNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_information_pages_render_with_more_navigation_and_a_return_link(): void
    {
        foreach ([
            'about' => 'About QueueFlow',
            'how-it-works' => 'How it works',
            'faq' => 'Frequently asked questions',
            'help' => 'Help and Support',
            'terms' => 'Terms and Conditions',
            'privacy' => 'Privacy Policy',
        ] as $slug => $title) {
            $this->get(route('more.information', $slug))
                ->assertOk()
                ->assertViewIs('more.information')
                ->assertSee($title)
                ->assertSee('href="'.route('more.show').'"', false)
                ->assertDontSee('underline underline-offset-4', false)
                ->assertSee('data-active-destination="more"', false);
        }
    }

    public function test_account_and_more_link_to_available_information_and_mark_deferred_items(): void
    {
        $this->get(route('account.show'))
            ->assertOk()
            ->assertSee('href="'.route('more.information', 'help').'"', false)
            ->assertSee('Help and support')
            ->assertSee('Coming soon');

        $this->get(route('more.show'))
            ->assertOk()
            ->assertSee('href="'.route('more.information', 'about').'"', false)
            ->assertSee('href="'.route('more.information', 'how-it-works').'"', false)
            ->assertSee('href="'.route('more.information', 'faq').'"', false)
            ->assertSee('href="'.route('more.information', 'help').'"', false)
            ->assertSee('href="'.route('more.information', 'terms').'"', false)
            ->assertSee('href="'.route('more.information', 'privacy').'"', false)
            ->assertSee('Terms and Conditions')
            ->assertSee('Privacy Policy')
            ->assertDontSee('Coming soon')
            ->assertSee('Contact us')
            ->assertSee('Support contact details are not available in this preview.')
            ->assertSee('disabled', false);
    }

    public function test_about_page_renders_mobile_information_sections_with_factual_capabilities(): void
    {
        $this->get(route('more.information', 'about'))
            ->assertOk()
            ->assertSee('QueueFlow at a glance')
            ->assertSee('Key capabilities')
            ->assertSeeInOrder([
                'Discover places and services',
                'Check queue availability',
                'Join an available queue',
                'Follow your queue ticket',
                'For customers',
                'For service teams',
            ])
            ->assertSee('M20 10c0 5-8 11-8 11S4 15 4 10', false)
            ->assertSee('M12 7v5l3 2', false)
            ->assertSee('M11 6h10M11 12h10M11 18h10', false)
            ->assertSee('join an available queue, and follow their ticket from the same device')
            ->assertSee('organize queues, manage customer flow, and keep customers informed')
            ->assertDontSee('data-faq-accordion', false);
    }

    public function test_how_it_works_renders_a_four_step_timeline_and_qr_preview_note(): void
    {
        $this->get(route('more.information', 'how-it-works'))
            ->assertOk()
            ->assertSee('relative z-10 -mt-7 px-5 pb-36', false)
            ->assertSee('data-how-it-works-timeline', false)
            ->assertSee('Four simple steps')
            ->assertSeeInOrder([
                '01',
                'Find a place',
                '02',
                'Check the service',
                '03',
                'Join when available',
                '04',
                'Keep your ticket handy',
            ])
            ->assertSee('-bottom-4 left-5 w-px', false)
            ->assertSee('About service QR codes')
            ->assertSee('Scanning a service QR code opens a preview. It does not automatically join the queue or create a ticket.')
            ->assertDontSee('data-faq-accordion', false);
    }

    public function test_help_page_renders_self_service_links_tips_and_contact_status(): void
    {
        $this->get(route('more.information', 'help'))
            ->assertOk()
            ->assertSee('relative z-10 -mt-7 px-5 pb-36', false)
            ->assertSee('data-help-content', false)
            ->assertSee('How can we help?')
            ->assertSee('href="'.route('more.information', 'faq').'"', false)
            ->assertSee('Frequently asked questions')
            ->assertSee('View FAQ')
            ->assertSeeInOrder([
                'Queue unavailable',
                'Finding your ticket',
                'Understanding service QR codes',
                'Contact information',
            ])
            ->assertSee('Scanning a service QR code opens a preview. It does not automatically join the queue or create a ticket.')
            ->assertSee('Official support contact details are not available in this preview.')
            ->assertDontSee('data-faq-accordion', false)
            ->assertDontSee('data-how-it-works-timeline', false);
    }

    public function test_faq_renders_accessible_accordion_with_factual_answers(): void
    {
        $this->get(route('more.information', 'faq'))
            ->assertOk()
            ->assertSee('data-faq-accordion', false)
            ->assertSee('data-faq-trigger', false)
            ->assertSee('type="button" aria-expanded="false"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="faq-answer-1"', false)
            ->assertSee('id="faq-answer-1"', false)
            ->assertSee('M2 4h6a4 4 0 0 1 4 4v13', false)
            ->assertSee('M3 3h6v6H3z', false)
            ->assertSee('M20 14v3M20 20h.01', false)
            ->assertSee('M4 21a8 8 0 0 1 16 0', false)
            ->assertSee('M3 5h18v5a2 2 0 0 0 0 4v5H3', false)
            ->assertSee('rounded-2xl bg-customer-indigo/5 p-4', false)
            ->assertDontSee('border-t border-customer-line/70', false)
            ->assertSeeInOrder(['QR code preview', 'Service availability', 'Guest access', 'Your ticket'])
            ->assertSee('Does scanning a service QR code join the queue?')
            ->assertSee('No. It opens the service preview. You join only if you continue through and submit the separate join flow.');
    }

    public function test_legal_drafts_render_structured_content_and_compact_draft_status(): void
    {
        $documents = [
            'terms' => [
                'sections' => ['1. About QueueFlow', '2. Acceptance of Terms', '3. Permitted Use', '4. Queue Participation and Tickets', '5. Guest Sessions and Saved Information', '6. Availability and Changes', '7. Intellectual Property', '8. Disclaimer', '9. Changes to These Terms', '10. Contact'],
                'review' => 'This document is being finalized before public release.',
                'content' => 'A demonstration ticket does not guarantee service from a real business.',
                'statements' => [
                    'QueueFlow is not currently a guaranteed real-world queue service.',
                    'Demonstration queues and data may be reset during development and testing.',
                    'Testers should not submit real sensitive, confidential, or unnecessary personal information.',
                    'Attempt to access another user\'s private information or tickets, or business functions, without authorization.',
                ],
            ],
            'privacy' => [
                'sections' => ['1. Introduction', '2. Information Processed', '3. How Information Is Used', '4. Cookies and Guest Sessions', '5. Queue and Ticket Information', '6. Information Sharing', '7. Retention and Deletion', '8. Security', '9. Your Privacy Choices', '10. International Processing', '11. Changes to This Policy', '12. Privacy Contact'],
                'review' => 'This document is being finalized before public release.',
                'content' => 'The backend currently has no general automated retention or purge process for queue and related records.',
                'statements' => [
                    'Staff and business account details: email address, first and last name, optional phone number, password hash, account identifier, roles, and business memberships.',
                    'Queue information: ticket identifiers and numbers, queue participation and status, party size when provided, and relevant timestamps.',
                    'QueueFlow also uses a CSRF/XSRF cookie to support protection of requests that change application data.',
                    'Authorized business and staff users can access operational queue information according to their assigned access.',
                    'Business, branch, and service information, including submitted branch location details, may be publicly visible through discovery features.',
                    'The reviewed QR scanner implementation processes camera images locally in the browser; the scanner does not upload camera images to the API.',
                    'Browser-based live ticket updates connect to the QueueFlow API.',
                    'expiration does not necessarily mean that the underlying record is physically deleted.',
                    'The Account “Clear data” control removes saved guest ticket references and favourite business identifiers from the Laravel session.',
                    'It does not delete Spring Boot queue records, technical logs, or notifications; sign out authenticated staff; or remove pending queue-join attempts.',
                    'The application uses authentication and authorization checks, CSRF protection for browser requests, password hashing, ticket ownership checks, and protections for authentication and guest ticket tokens.',
                    'The hosting provider, infrastructure providers, server and database regions, and any cross-border processing arrangements have not yet been confirmed.',
                ],
            ],
        ];

        foreach ($documents as $slug => $document) {
            $response = $this->get(route('more.information', $slug));

            $response
                ->assertOk()
                ->assertSee('data-legal-document', false)
                ->assertSee('data-legal-container', false)
                ->assertSee('data-legal-status', false)
                ->assertSee('<details', false)
                ->assertSee('<summary', false)
                ->assertSee('data-legal-toc', false)
                ->assertSee('aria-label="On this page"', false)
                ->assertSee('data-legal-sections', false)
                ->assertSee('divide-y divide-customer-line/70', false)
                ->assertSee('scroll-mt-5', false)
                ->assertSee('Portfolio demo · Policy draft')
                ->assertSee($document['review'])
                ->assertDontSee('Draft — Pending Review')
                ->assertDontSee('Last updated:')
                ->assertDontSee('Pending technical and legal review.')
                ->assertDontSee('Pending data-handling audit and legal review.')
                ->assertDontSee('To be confirmed before publication.')
                ->assertSeeInOrder($document['sections'])
                ->assertSee($document['content'])
                ->assertDontSee('type="checkbox"', false)
                ->assertSee('data-active-destination="more"', false);

            foreach ($document['statements'] as $statement) {
                $response->assertSee($statement);
            }

            $this->assertSame(1, substr_count($response->getContent(), 'data-legal-container'));
            $this->assertSame(count($document['sections']), substr_count($response->getContent(), 'data-legal-section>'));
            $this->assertSame(count($document['sections']), substr_count($response->getContent(), 'data-legal-toc-link'));

            foreach (array_keys($document['sections']) as $index) {
                $section = $index + 1;

                $response
                    ->assertSee('href="#'.$slug.'-section-'.$section.'"', false)
                    ->assertSee('id="'.$slug.'-section-'.$section.'"', false);
            }
        }

        $this->get(route('more.information', 'privacy'))
            ->assertDontSee('requires further verification')
            ->assertDontSee('does not necessarily delete records stored by the Spring API');
    }

    public function test_unapproved_information_destinations_return_not_found(): void
    {
        $this->get('/more/cookies')->assertNotFound();
        $this->get('/more/imprint')->assertNotFound();
    }
}
