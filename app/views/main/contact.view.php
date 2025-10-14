<x-layout-app title="Contact">

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow pb-5">
                <div class="card-body p-4">
                    <h2 class="card-title mb-5 border-bottom bg-body-secondary p-3 rounded">
                        <i class="bi bi-envelope"></i> Contact Us
                    </h2>
                    <div class="row align-items-stretch">
                        <!-- Contact Information -->
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

                        <!-- Contact Form -->
                        <div class="col-md-6 d-flex">
                            <div class="border rounded w-100 d-flex flex-column">
                                <div class="bg-body-secondary p-3 rounded-top">
                                    <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Send us a Message</h5>
                                </div>

                                <div class="p-3 flex-fill">
                                    <form action="/contact" method="POST">
                                        @php echo csrf_field(); @endphp

                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Enter your name" required>
                                            <label for="name" class="form-label">Name</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input type="email" class="form-control" id="email" name="email" placeholder=""
                                                required>
                                            <label for="email" class="form-label">Email</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="subject" name="subject"
                                                placeholder="Enter the subject" required>
                                            <label for="subject" class="form-label">Subject</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="message" name="message" rows="4"
                                                placeholder="" required></textarea>
                                            <label for="message" class="form-label">Message</label>
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

</x-layout-app>