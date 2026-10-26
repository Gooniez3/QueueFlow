@extends('layouts.app')

@section('title', $information['title'].' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10"><h1 class="customer-page-title">{{ $information['title'] }}</h1><p class="customer-page-copy">QueueFlow information</p></div></header>

        <main @class([
            'relative z-10 -mt-7 px-5',
            'pb-36' => in_array(request()->route('page'), ['how-it-works', 'help', 'terms', 'privacy'], true),
            'pb-28' => ! in_array(request()->route('page'), ['how-it-works', 'help', 'terms', 'privacy'], true),
        ])>
            <a class="inline-flex min-h-11 items-center text-sm font-semibold text-customer-indigo focus-visible:outline-2 focus-visible:outline-customer-indigo" href="{{ route('more.show') }}">Back to More</a>
            @if (request()->route('page') === 'about')
                @php($aboutFeatures = [
                    ['icon' => 'map-pin', 'heading' => 'Discover places and services', 'body' => 'Browse participating businesses, branches, and services.'],
                    ['icon' => 'clock', 'heading' => 'Check queue availability', 'body' => 'Review queue information and whether joining is available.'],
                    ['icon' => 'ticket', 'heading' => 'Join an available queue', 'body' => 'Continue through the join flow when a service queue is open.'],
                    ['icon' => 'list-check', 'heading' => 'Follow your queue ticket', 'body' => 'Keep up with your ticket from the device where you joined.'],
                ])
                <section class="mt-3" aria-labelledby="information-heading">
                    <div class="rounded-[1.6rem] bg-white p-5 shadow-[0_16px_35px_-24px_rgba(21,17,63,0.55)]">
                        <span class="grid size-12 place-items-center rounded-2xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon class="size-6" name="route" /></span>
                        <h2 id="information-heading" class="mt-4 text-xl font-bold">QueueFlow at a glance</h2>
                        <p class="mt-2 text-sm leading-6 text-customer-muted">{{ $information['introduction'] }}</p>
                    </div>

                    <section class="mt-6" aria-labelledby="capabilities-heading">
                        <h2 id="capabilities-heading" class="px-1 text-lg font-bold">Key capabilities</h2>
                        <div class="mt-3 grid gap-3 min-[380px]:grid-cols-2">
                            @foreach ($aboutFeatures as $feature)
                                <article class="min-w-0 rounded-[1.4rem] bg-white p-4 shadow-[0_14px_32px_-26px_rgba(21,17,63,0.65)]">
                                    <span class="grid size-10 place-items-center rounded-xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon :name="$feature['icon']" /></span>
                                    <h3 class="mt-3 text-sm leading-5 font-bold">{{ $feature['heading'] }}</h3>
                                    <p class="mt-1.5 break-words text-xs leading-5 text-customer-muted">{{ $feature['body'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <div class="mt-6 space-y-3">
                        @foreach ($information['sections'] as $section)
                            <article class="flex min-w-0 items-start gap-4 rounded-[1.4rem] bg-white p-5 shadow-[0_14px_32px_-26px_rgba(21,17,63,0.65)]">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon :name="$loop->first ? 'user-round' : 'building-2'" /></span>
                                <div class="min-w-0">
                                    <h2 class="font-bold">{{ $section['heading'] }}</h2>
                                    <p class="mt-1.5 break-words text-sm leading-6 text-customer-muted">{{ $section['body'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @elseif (request()->route('page') === 'how-it-works')
                @php($stepIcons = ['map-pin', 'clock', 'ticket', 'list-check'])
                <section class="mt-3" aria-labelledby="information-heading">
                    <div class="px-1">
                        <h2 id="information-heading" class="text-xl font-bold">Four simple steps</h2>
                        <p class="mt-2 text-sm leading-6 text-customer-muted">{{ $information['introduction'] }}</p>
                    </div>

                    <ol class="mt-5 space-y-4" data-how-it-works-timeline>
                        @foreach ($information['sections'] as $section)
                            <li class="relative min-w-0 pl-13">
                                @unless ($loop->last)
                                    <span class="absolute top-10 -bottom-4 left-5 w-px -translate-x-1/2 bg-customer-indigo/20" aria-hidden="true"></span>
                                @endunless
                                <span class="absolute top-0 left-0 z-10 grid size-10 place-items-center rounded-full bg-customer-indigo text-xs font-bold text-white" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <article class="min-w-0 rounded-[1.4rem] bg-white p-4 shadow-[0_14px_32px_-26px_rgba(21,17,63,0.65)]">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon :name="$stepIcons[$loop->index]" /></span>
                                        <h3 class="min-w-0 text-base leading-6 font-bold"><span class="sr-only">Step {{ $loop->iteration }}: </span>{{ $section['heading'] }}</h3>
                                    </div>
                                    <p class="mt-3 break-words text-sm leading-6 text-customer-muted">{{ $section['body'] }}</p>
                                </article>
                            </li>
                        @endforeach
                    </ol>

                    <aside class="mt-5 flex min-w-0 items-start gap-3 rounded-2xl bg-customer-indigo/5 p-4" role="note">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white text-customer-indigo" aria-hidden="true"><x-customer-icon class="size-4" name="info-circle" /></span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold">About service QR codes</h2>
                            <p class="mt-1 break-words text-sm leading-6 text-customer-muted">Scanning a service QR code opens a preview. It does not automatically join the queue or create a ticket.</p>
                        </div>
                    </aside>
                </section>
            @elseif (request()->route('page') === 'help')
                @php($tipIcons = ['clock-alert', 'ticket', 'qr-code'])
                <section class="mt-3" aria-labelledby="information-heading" data-help-content>
                    <div class="flex min-w-0 items-start gap-3 px-1">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon name="life-buoy" /></span>
                        <div class="min-w-0">
                            <h2 id="information-heading" class="text-xl font-bold">How can we help?</h2>
                            <p class="mt-1 break-words text-sm leading-6 text-customer-muted">{{ $information['introduction'] }}</p>
                        </div>
                    </div>

                    <a class="mt-5 block min-w-0 rounded-[1.5rem] bg-white p-5 shadow-[0_16px_35px_-24px_rgba(21,17,63,0.55)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo" href="{{ route('more.information', 'faq') }}">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon name="book-open" /></span>
                            <div class="min-w-0 grow">
                                <h2 class="font-bold">Frequently asked questions</h2>
                                <p class="mt-1 break-words text-sm leading-6 text-customer-muted">{{ $information['sections'][0]['body'] }}</p>
                            </div>
                            <x-customer-icon class="size-5 shrink-0 text-customer-indigo" name="chevron-right" aria-hidden="true" />
                        </div>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-customer-indigo">View FAQ <span aria-hidden="true">&rsaquo;</span></span>
                    </a>

                    <section class="mt-6" aria-labelledby="troubleshooting-heading">
                        <h2 id="troubleshooting-heading" class="px-1 text-lg font-bold">Troubleshooting tips</h2>
                        <div class="mt-3 space-y-3">
                            @foreach (array_slice($information['sections'], 1, 3) as $tip)
                                <article class="flex min-w-0 items-start gap-3 rounded-[1.4rem] bg-white p-4 shadow-[0_14px_32px_-26px_rgba(21,17,63,0.65)]">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon :name="$tipIcons[$loop->index]" /></span>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold">{{ $tip['heading'] }}</h3>
                                        <p class="mt-1 break-words text-sm leading-6 text-customer-muted">{{ $tip['body'] }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <aside class="mt-5 flex min-w-0 items-start gap-3 rounded-2xl bg-customer-indigo/5 p-4" role="note" data-help-contact>
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white text-customer-indigo" aria-hidden="true"><x-customer-icon class="size-4" name="info-circle" /></span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold">{{ $information['sections'][4]['heading'] }}</h2>
                            <p class="mt-1 break-words text-sm leading-6 text-customer-muted">{{ $information['sections'][4]['body'] }}</p>
                        </div>
                    </aside>
                </section>
            @elseif (in_array(request()->route('page'), ['terms', 'privacy'], true))
                @php($legalPage = request()->route('page'))
                <section class="mt-3" aria-labelledby="information-heading" data-legal-document>
                    <article class="min-w-0 overflow-hidden rounded-[1.6rem] bg-white shadow-[0_16px_35px_-24px_rgba(21,17,63,0.55)]" data-legal-container>
                        <div class="space-y-3 p-4 min-[375px]:p-5">
                            <div class="min-w-0 px-1 py-1" role="note" data-legal-status>
                                <h2 id="information-heading" class="inline-flex items-center gap-1.5 rounded-full bg-customer-indigo/5 px-2.5 py-1 text-xs font-semibold text-customer-indigo">{{ $information['status'] }}</h2>
                                <p class="mt-1.5 break-words text-xs leading-5 text-customer-muted">{{ $information['reviewNote'] }}</p>
                            </div>

                            <details class="group overflow-hidden rounded-2xl border border-customer-line/80 bg-white" data-legal-toc>
                                <summary class="flex min-h-12 cursor-pointer list-none items-center gap-3 px-3.5 py-2.5 text-sm font-bold focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-customer-indigo [&::-webkit-details-marker]:hidden">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon class="size-4" name="book-open" /></span>
                                    <span class="grow">On this page</span>
                                    <x-customer-icon class="size-4 shrink-0 text-customer-muted transition-transform duration-200 group-open:rotate-90 motion-reduce:transition-none" name="chevron-right" aria-hidden="true" />
                                </summary>
                                <nav class="border-t border-customer-line/70 px-3.5 py-2" aria-label="On this page">
                                    <ol class="space-y-0.5">
                                        @foreach ($information['sections'] as $section)
                                            <li>
                                                <a class="block rounded-lg px-2 py-2 text-sm leading-5 text-customer-muted hover:bg-customer-indigo/5 hover:text-customer-indigo focus-visible:bg-customer-indigo/5 focus-visible:text-customer-indigo focus-visible:outline-2 focus-visible:outline-customer-indigo" href="#{{ $legalPage }}-section-{{ $loop->iteration }}" data-legal-toc-link>{{ $section['heading'] }}</a>
                                            </li>
                                        @endforeach
                                    </ol>
                                </nav>
                            </details>
                        </div>

                        <div class="divide-y divide-customer-line/70 border-t border-customer-line/70 px-4 min-[375px]:px-5" data-legal-sections>
                            @foreach ($information['sections'] as $section)
                                @php([$sectionNumber, $sectionTitle] = explode('. ', $section['heading'], 2))
                                <section id="{{ $legalPage }}-section-{{ $loop->iteration }}" class="min-w-0 scroll-mt-5 py-5 last:pb-6" data-legal-section>
                                    <h2 class="flex min-w-0 items-start gap-3 text-base leading-6 font-bold text-customer-navy">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-customer-indigo/10 text-xs font-bold text-customer-indigo" aria-hidden="true">{{ $sectionNumber }}</span>
                                        <span class="min-w-0 pt-1"><span class="sr-only">{{ $sectionNumber }}. </span>{{ $sectionTitle }}</span>
                                    </h2>
                                    <div class="mt-3 space-y-3 text-sm leading-6 text-customer-muted">
                                        @foreach ($section['paragraphs'] as $paragraph)
                                            <p class="break-words">{{ $paragraph }}</p>
                                        @endforeach
                                        @if (isset($section['items']))
                                            <ul class="list-disc space-y-1.5 pl-5 marker:text-customer-indigo">
                                                @foreach ($section['items'] as $item)
                                                    <li class="break-words pl-1">{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @foreach ($section['afterParagraphs'] ?? [] as $paragraph)
                                            <p class="break-words">{{ $paragraph }}</p>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </article>
                </section>
            @elseif (request()->route('page') === 'faq')
                @php($faqIcons = [
                    'Does scanning a service QR code join the queue?' => 'qr-code',
                    'Why is joining unavailable?' => 'clock-alert',
                    'Do I need an account?' => 'user-round',
                    'Where can I find my ticket?' => 'ticket',
                ])
                @php($faqLabels = [
                    'Does scanning a service QR code join the queue?' => 'QR code preview',
                    'Why is joining unavailable?' => 'Service availability',
                    'Do I need an account?' => 'Guest access',
                    'Where can I find my ticket?' => 'Your ticket',
                ])
                <section class="mt-3" aria-labelledby="information-heading">
                    <div class="px-1">
                        <div class="flex items-center gap-3">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon name="book-open" /></span>
                            <div>
                                <h2 id="information-heading" class="text-xl font-bold">{{ $information['title'] }}</h2>
                                <p class="mt-1 text-sm leading-6 text-customer-muted">{{ $information['introduction'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3" data-faq-accordion>
                        @foreach ($information['sections'] as $section)
                            @php($panelId = 'faq-answer-'.$loop->iteration)
                            <article class="overflow-hidden rounded-[1.4rem] border border-customer-line/70 bg-white shadow-[0_14px_32px_-26px_rgba(21,17,63,0.65)]">
                                <h3>
                                    <button class="group flex min-h-18 w-full items-center gap-3 px-4 py-4 text-left focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-customer-indigo" type="button" aria-expanded="false" aria-controls="{{ $panelId }}" data-faq-trigger>
                                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-customer-indigo/10 text-customer-indigo" aria-hidden="true"><x-customer-icon class="size-[1.125rem]" :name="$faqIcons[$section['heading']]" /></span>
                                        <span class="grow text-[0.95rem] leading-6 font-semibold">{{ $section['heading'] }}</span>
                                        <x-customer-icon class="size-5 shrink-0 text-customer-muted transition-transform duration-200 group-aria-expanded:rotate-90 motion-reduce:transition-none" name="chevron-right" aria-hidden="true" />
                                    </button>
                                </h3>
                                <div id="{{ $panelId }}" class="px-4 pb-4" hidden>
                                    <div class="min-w-0 rounded-2xl bg-customer-indigo/5 p-4">
                                        <p class="flex items-center gap-2 text-xs font-bold text-customer-indigo">
                                            <x-customer-icon class="size-4 shrink-0" :name="$faqIcons[$section['heading']]" aria-hidden="true" />
                                            <span>{{ $faqLabels[$section['heading']] }}</span>
                                        </p>
                                        <p class="mt-2 break-words text-sm leading-6 text-customer-muted">{{ $section['body'] }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="mt-3 rounded-[1.6rem] bg-white p-5 shadow-[0_16px_35px_-24px_rgba(21,17,63,0.55)]" aria-labelledby="information-heading">
                    <h2 id="information-heading" class="text-xl font-bold">{{ $information['title'] }}</h2>
                    <p class="mt-3 text-sm leading-6 text-customer-muted">{{ $information['introduction'] }}</p>
                    <div class="mt-4 divide-y divide-customer-line/70">
                        @foreach ($information['sections'] as $section)
                            <article class="py-4 first:pt-0 last:pb-0">
                                <h3 class="font-semibold">{{ $section['heading'] }}</h3>
                                <p class="mt-1 text-sm leading-6 text-customer-muted">{{ $section['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>
    </div>
@endsection
