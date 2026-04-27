<x-layout-app title="Contact">

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow pb-5">
                <div class="card-body p-4">
                    <h2 class="card-title mb-5 border-bottom bg-body-secondary p-3 rounded">
                        <i class="bi bi-envelope"></i> Contact Us
                    </h2>
                    @php
                    $showGetInTouchCard = $contactInfo->show_get_in_touch_card ?? true;
                    $formColumnClass = $showGetInTouchCard ? 'col-md-6 d-flex' : 'col-12 d-flex';
                    @endphp
                    <div class="row align-items-stretch">
                        <!-- Contact Information -->
                        @if($showGetInTouchCard)
                        <div class="col-md-6 d-flex">
                            <div class="border rounded w-100 d-flex flex-column">
                                <div class="bg-body-secondary p-3 rounded-top">
                                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Get In Touch</h5>
                                </div>

                                <div class="p-3 flex-fill">
                                    @if($contactInfo->company_name)
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1 border-bottom pb-1">
                                            <i class="bi bi-building"></i> Company
                                        </h6>
                                        <p class="mb-0">{{ $contactInfo->company_name }}</p>
                                    </div>
                                    @endif

                                    @if($contactInfo->address || $contactInfo->city)
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1 border-bottom pb-1">
                                            <i class="bi bi-geo-alt"></i> Address
                                        </h6>
                                        <p class="mb-0">
                                            @if($contactInfo->address)
                                            {{ $contactInfo->address }}<br>
                                            @endif
                                            @if($contactInfo->postal_code || $contactInfo->city)
                                            {{ $contactInfo->postal_code }} {{ $contactInfo->city }}
                                            @endif
                                        </p>
                                    </div>
                                    @endif

                                    @if($contactInfo->email)
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1 border-bottom pb-1">
                                            <i class="bi bi-envelope"></i> Email
                                        </h6>
                                        <p class="mb-0">
                                            <a href="mailto:{{ $contactInfo->email }}">{{ $contactInfo->email }}</a>
                                        </p>
                                    </div>
                                    @endif

                                    @if($contactInfo->phone)
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1 border-bottom pb-1">
                                            <i class="bi bi-telephone"></i> Phone
                                        </h6>
                                        <p class="mb-0">
                                            <a href="tel:{{ $contactInfo->phone }}">{{ $contactInfo->phone }}</a>
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Contact Form -->
                        <div class="{{ $formColumnClass }}">
                            <div class="border rounded w-100 d-flex flex-column">
                                <div class="bg-body-secondary p-3 rounded-top">
                                    <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Send us a Message</h5>
                                </div>

                                <div class="p-3 flex-fill">
                                    <form action="/contact" method="POST">
                                        @php echo csrf_field(); @endphp

                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Enter your name"
                                                value="{{ $oldInput['name'] ?? '' }}"
                                                required>
                                            <label for="name" class="form-label">Name</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input type="email" class="form-control" id="email" name="email" placeholder=""
                                                value="{{ $oldInput['email'] ?? '' }}"
                                                required>
                                            <label for="email" class="form-label">Email</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="subject" name="subject"
                                                placeholder="Enter the subject"
                                                value="{{ $oldInput['subject'] ?? '' }}"
                                                required>
                                            <label for="subject" class="form-label">Subject</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="message" name="message" rows="4"
                                                placeholder="" required>{{ $oldInput['message'] ?? '' }}</textarea>
                                            <label for="message" class="form-label">Message</label>
                                        </div>

                                        <div class="mb-3">
                                            <label for="captcha" class="form-label">Security Check</label>
                                            <div class="border rounded p-3 bg-body-tertiary">
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <img
                                                        src="{{ route('main/contact/captcha') }}"
                                                        alt="CAPTCHA challenge"
                                                        id="contact-captcha-image"
                                                        width="170"
                                                        height="56"
                                                        class="border rounded bg-white">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        id="contact-captcha-refresh">
                                                        <i class="bi bi-arrow-clockwise"></i> Refresh Code
                                                    </button>
                                                </div>
                                                <p class="form-text mb-2 mt-2">Enter the characters shown in the image.</p>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    id="captcha"
                                                    name="captcha"
                                                    inputmode="text"
                                                    autocomplete="off"
                                                    autocapitalize="characters"
                                                    spellcheck="false"
                                                    maxlength="5"
                                                    required>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-send"></i> Send Message
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const image = document.getElementById('contact-captcha-image');
            const refresh = document.getElementById('contact-captcha-refresh');

            if (!image || !refresh) {
                return;
            }

            const refreshCaptcha = () => {
                image.src = '{{ route('main/contact/captcha') }}?v=' + Date.now();
            };

            refresh.addEventListener('click', refreshCaptcha);
            image.addEventListener('click', refreshCaptcha);
        })();
    </script>

</x-layout-app>
