@extends('layouts.theme')

@section('title', 'Subscribe to Our Newsletter')

@section('head')
<style>
    .subscription-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        max-width: 500px;
        width: 100%;
    }
    .subscription-card h1 {
        color: #667eea;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .subscription-card p {
        color: #6c757d;
        margin-bottom: 30px;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-subscribe {
        background: #90bb13;
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        transition: transform 0.2s;
    }
    .btn-subscribe:hover {
        transform: translateY(-2px);
        background: #90bb13;
    }
    .btn-subscribe:disabled {
        background: #6c757d;
        transform: none;
    }
    .alert {
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }
    .success-icon {
        font-size: 3rem;
        color: #28a745;
        margin-bottom: 20px;
    }
    .error-icon {
        font-size: 3rem;
        color: #dc3545;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('content')
<div class="subscription-card">
    <div class="text-center mb-4">
        <h1>Join Our Newsletter</h1>
        <p>Stay updated with our latest news and exclusive content.</p>
    </div>

    <!-- Success Message (Hidden by default) -->
    <div id="successMessage" class="alert alert-success text-center" style="display: none;">
        <div class="success-icon">✓</div>
        <h4>Successfully Subscribed!</h4>
        <p class="mb-0">Thank you for subscribing. Check your email for confirmation.</p>
    </div>

    <!-- Subscription Form -->
    <form id="subscriptionForm" method="POST">
        @csrf

        <!-- Alert Container for Errors -->
        <div id="alertContainer"></div>

        <!-- Email Input -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input
                type="email"
                class="form-control form-control-lg"
                id="email"
                name="email"
                placeholder="your.email@example.com"
                required
                aria-describedby="emailHelp"
            >
            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
            <div class="invalid-feedback" id="emailError"></div>
        </div>

        <!-- Name Input (Optional) -->
        <div class="mb-3">
            <label for="name" class="form-label">Name (Optional)</label>
            <input
                type="text"
                class="form-control form-control-lg"
                id="name"
                name="name"
                placeholder="Your Name"
            >
            <div class="invalid-feedback" id="nameError"></div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg btn-subscribe" id="submitBtn">
                <span id="submitText">Subscribe Now</span>
                <span id="submitSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
            </button>
        </div>
    </form>

    <!-- Privacy Notice -->
    <div class="text-center mt-4">
        <small class="text-muted">
            By subscribing, you agree to receive emails from us. You can unsubscribe at any time.
        </small>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('subscriptionForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');
    const emailInput = document.getElementById('email');
    const nameInput = document.getElementById('name');
    const alertContainer = document.getElementById('alertContainer');
    const successMessage = document.getElementById('successMessage');

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Clear previous validation errors
    function clearErrors() {
        emailInput.classList.remove('is-invalid');
        nameInput.classList.remove('is-invalid');
        document.getElementById('emailError').textContent = '';
        document.getElementById('nameError').textContent = '';
        alertContainer.innerHTML = '';
    }

    // Show error alert
    function showError(message) {
        const alert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertContainer.innerHTML = alert;
    }

    // Show validation errors
    function showValidationErrors(errors) {
        if (errors.email) {
            emailInput.classList.add('is-invalid');
            document.getElementById('emailError').textContent = errors.email[0];
        }
        if (errors.name) {
            nameInput.classList.add('is-invalid');
            document.getElementById('nameError').textContent = errors.name[0];
        }
    }

    // Set loading state
    function setLoading(isLoading) {
        if (isLoading) {
            submitBtn.disabled = true;
            submitText.textContent = 'Subscribing...';
            submitSpinner.style.display = 'inline-block';
        } else {
            submitBtn.disabled = false;
            submitText.textContent = 'Subscribe Now';
            submitSpinner.style.display = 'none';
        }
    }

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        clearErrors();
        setLoading(true);

        // Get form data
        const formData = new FormData(form);

        // Send AJAX request
        fetch('{{ route("newsletter.subscribe") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            return response.json().then(data => {
                if (!response.ok) {
                    throw data;
                }
                return data;
            });
        })
        .then(data => {
            // Success - hide form and show success message
            form.style.display = 'none';
            successMessage.style.display = 'block';

            // Optional: Reset form after delay
            setTimeout(() => {
                form.reset();
                form.style.display = 'block';
                successMessage.style.display = 'none';
            }, 5000);
        })
        .catch(error => {
            console.error('Error:', error);

            if (error.errors) {
                // Validation errors
                showValidationErrors(error.errors);
            } else if (error.message) {
                // General error message
                showError(error.message);
            } else {
                // Fallback error message
                showError('An error occurred. Please try again later.');
            }
        })
        .finally(() => {
            setLoading(false);
        });
    });

    // Real-time email validation
    emailInput.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
            document.getElementById('emailError').textContent = '';
        }
    });

    // Real-time name validation
    nameInput.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
            document.getElementById('nameError').textContent = '';
        }
    });
});
</script>
@endsection
