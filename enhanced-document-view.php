<!-- Add this modal HTML before the closing </body> tag in your document_view_details_of_exams.php -->

<!-- Email Sending Modal -->
<div class="modal fade" id="emailSendingModal" tabindex="-1" role="dialog" aria-labelledby="emailSendingModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="emailSendingModalLabel">
                    <i class="fas fa-envelope"></i> Sending Emails
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <h5 class="mb-3">Please Wait</h5>
                <p class="mb-2"><strong>Mail Sending in Progress...</strong></p>
                <p class="text-muted">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Please don't refresh or close this page
                </p>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 100%"></div>
                </div>
                <div id="emailProgress" class="mt-3">
                    <small class="text-muted">Preparing to send emails...</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="emailSuccessModal" tabindex="-1" role="dialog" aria-labelledby="emailSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="emailSuccessModalLabel">
                    <i class="fas fa-check-circle"></i> Email Sending Complete
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                </div>
                <h5 class="text-success">Success!</h5>
                <p id="successMessage">Emails have been sent successfully.</p>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Page will refresh automatically in <span id="countdown">3</span> seconds...
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced sendAllEmails function - replace your existing function with this
function sendAllEmails(assessmentId) {
    const removedEmails = Array.from(document.querySelectorAll('.remove-student:checked')).map(checkbox => checkbox.value);

    if (removedEmails.length > 0) {
        const confirmMessage = `You have selected ${removedEmails.length} student(s) to exclude from the email list. Continue sending emails to the remaining students?`;
        if (!confirm(confirmMessage)) {
            return;
        }
    }

    // Show the loading modal
    $('#emailSendingModal').modal('show');
    
    // Disable the send button
    const button = document.getElementById('sendAllEmails');
    button.disabled = true;
    button.style.opacity = '0.6';

    // Update progress message
    document.getElementById('emailProgress').innerHTML = '<small class="text-info">Sending emails to students...</small>';

    $.ajax({
        url: 'transection_exams/check_allocate_assi_document.php',
        type: 'POST',
        dataType: 'json',
        data: {
            id: assessmentId,
            removed_emails: removedEmails
        },
        success: function(response) {
            console.log('Response from server: ', response);

            // Hide the loading modal
            $('#emailSendingModal').modal('hide');

            let message = response.message;
            if (response.failedEmails && response.failedEmails.length > 0) {
                message += "\n\n⚠️ Failed emails:\n";
                response.failedEmails.forEach(emailObj => {
                    message += `• ${emailObj.email}: ${emailObj.error}\n`;
                });
            }

            if (response.success) {
                // Show success modal
                document.getElementById('successMessage').textContent = message;
                $('#emailSuccessModal').modal('show');

                // Update button appearance
                button.innerHTML = '<i class="fas fa-check"></i> Emails Sent Successfully';
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');

                // Start countdown and auto-refresh
                let countdown = 3;
                const countdownElement = document.getElementById('countdown');
                
                const countdownInterval = setInterval(function() {
                    countdown--;
                    countdownElement.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        $('#emailSuccessModal').modal('hide');
                        location.reload();
                    }
                }, 1000);

            } else {
                // Show error message
                alert('Error: ' + message);
                
                // Re-enable button
                button.disabled = false;
                button.style.opacity = '1';
            }
        },
        error: function(xhr, status, error) {
            console.error('Error: ' + error);
            
            // Hide the loading modal
            $('#emailSendingModal').modal('hide');
            
            alert('An error occurred while sending the emails. Please try again.');

            // Re-enable button
            button.disabled = false;
            button.style.opacity = '1';
        }
    });
}

// Prevent page refresh/close during email sending
let emailSendingInProgress = false;

// Show warning when user tries to leave during email sending
window.addEventListener('beforeunload', function(e) {
    if (emailSendingInProgress) {
        const confirmationMessage = 'Email sending is in progress. Are you sure you want to leave?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});

// Update the email sending status
$('#emailSendingModal').on('shown.bs.modal', function() {
    emailSendingInProgress = true;
});

$('#emailSendingModal').on('hidden.bs.modal', function() {
    emailSendingInProgress = false;
});
</script>

<style>
/* Additional CSS for better modal appearance */
.modal-backdrop.show {
    opacity: 0.8;
}

.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% {
        background-position: 1rem 0;
    }
    100% {
        background-position: 0 0;
    }
}

.spinner-border {
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}

#emailSendingModal .modal-content {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

#emailSuccessModal .modal-content {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
</style>
