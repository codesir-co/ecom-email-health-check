<div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Submit Data to Google Sheet', 'ecehc' ); ?></h1>
        <p><?php esc_html_e( 'Use this form to send data directly to your configured Google Sheet.', 'ecehc' ); ?></p>

        <?php
        // Display admin notices (success/error messages)
        settings_errors( 'ecehc_form_messages' );
        ?>

        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md mx-auto my-8">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-6"><?php esc_html_e( 'Contact Us', 'ecehc' ); ?></h2>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" class="space-y-4">
                <?php
                // WordPress Nonce field for security.
                // This is crucial to prevent CSRF attacks.
                wp_nonce_field( 'ecehc_submit_survey_form', 'ecehc_survey_form_nonce' );
                ?>
                <input type="hidden" name="action" value="ecehc_submit_survey_form">

                <div>
                    <label for="ecom_name" class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e( 'Name', 'ecehc' ); ?></label>
                    <input type="text" id="ecom_name" name="name" required
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="ecom_email" class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e( 'Email', 'ecehc' ); ?></label>
                    <input type="email" id="ecom_email" name="email" required
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="ecom_message" class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e( 'Message', 'ecehc' ); ?></label>
                    <textarea id="ecom_message" name="message" rows="4" required
                              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                </div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <?php esc_html_e( 'Submit to Google Sheet', 'ecehc' ); ?>
                </button>
            </form>
        </div>
    </div>
    <style>
        /* Basic styling for the admin page to center the form */
        .wrap .max-w-md {
            max-width: 400px; /* Adjust as needed */
        }
        .wrap .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }
        /* Inline CSS for the form elements, mimicking Tailwind's utility classes */
        .bg-white { background-color: #fff; }
        .p-8 { padding: 2rem; }
        .rounded-lg { border-radius: 0.5rem; }
        .shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .w-full { width: 100%; }
        .my-8 { margin-top: 2rem; margin-bottom: 2rem; }
        .text-3xl { font-size: 1.875rem; }
        .font-bold { font-weight: 700; }
        .text-center { text-align: center; }
        .text-gray-800 { color: #1f2937; }
        .mb-6 { margin-bottom: 1.5rem; }
        .space-y-4 > *:not([hidden]) ~ *:not([hidden]) { margin-top: 1rem; }
        .block { display: block; }
        .text-sm { font-size: 0.875rem; }
        .font-medium { font-weight: 500; }
        .text-gray-700 { color: #374151; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mt-1 { margin-top: 0.25rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .border { border-width: 1px; }
        .border-gray-300 { border-color: #d1d5db; }
        .rounded-md { border-radius: 0.375rem; }
        .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .focus\:ring-blue-500:focus { --tw-ring-color: #3b82f6; ring-color: var(--tw-ring-color); }
        .focus\:border-blue-500:focus { border-color: #3b82f6; }
        .sm\:text-sm { font-size: 0.875rem; }
        .flex { display: flex; }
        .justify-center { justify-content: center; }
        .border-transparent { border-color: transparent; }
        .text-lg { font-size: 1.125rem; }
        .text-white { color: #fff; }
        .bg-blue-600 { background-color: #2563eb; }
        .hover\:bg-blue-700:hover { background-color: #1d4ed8; }
        .focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }
        .focus\:ring-2:focus { --tw-ring-offset-width: 2px; ring-width: 2px; }
        .focus\:ring-offset-2:focus { --tw-ring-offset-width: 2px; }
        .transition { transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter; }
        .duration-150 { transition-duration: 150ms; }
        .ease-in-out { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
    </style>