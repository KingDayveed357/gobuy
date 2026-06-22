<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true" style="backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pb-5 px-4">
                <div class="mb-4" id="actionModalIconContainer">
                    <span class="fa-stack fa-2x">
                        <i class="fas fa-circle fa-stack-2x" id="actionModalIconBg"></i>
                        <i class="fas fa-stack-1x" id="actionModalIcon"></i>
                    </span>
                </div>
                <h4 class="mb-3 text-body-highlight" id="actionModalLabel">Confirm Action</h4>
                <p class="text-body-tertiary mb-4" id="actionModalMessage">Are you sure you want to proceed?</p>
                <form id="actionForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" id="actionFormMethod" value="POST">
                </form>
                
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" id="actionModalCancelBtn">Cancel</button>
                    <button type="button" class="btn px-4" id="actionModalSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="actionModalSpinner"></span>
                        <span id="actionModalSubmitText">Yes, proceed</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const actionModal = document.getElementById('actionModal');
        let currentTargetForm = null;

        if (actionModal) {
            actionModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const formId = button.getAttribute('data-form-id');
                const actionUrl = button.getAttribute('data-action');
                
                if (formId) {
                    currentTargetForm = document.getElementById(formId);
                } else if (actionUrl) {
                    const method = button.getAttribute('data-method') || 'POST';
                    document.getElementById('actionForm').action = actionUrl;
                    document.getElementById('actionFormMethod').value = method;
                    currentTargetForm = document.getElementById('actionForm');
                }

                const title = button.getAttribute('data-title') || 'Confirm Action';
                const message = button.getAttribute('data-message') || 'Are you sure you want to proceed?';
                const confirmText = button.getAttribute('data-confirm-text') || 'Yes, proceed';
                const variant = button.getAttribute('data-variant') || 'danger';

                document.getElementById('actionModalLabel').textContent = title;
                document.getElementById('actionModalMessage').textContent = message;
                document.getElementById('actionModalSubmitText').textContent = confirmText;

                const submitBtn = document.getElementById('actionModalSubmitBtn');
                submitBtn.className = 'btn px-4 btn-' + variant;
                
                const iconBg = document.getElementById('actionModalIconBg');
                const icon = document.getElementById('actionModalIcon');
                
                iconBg.className = 'fas fa-circle fa-stack-2x text-' + variant + '-subtle';
                icon.className = 'fas fa-stack-1x text-' + variant;
                
                if(variant === 'danger') {
                    icon.classList.add('fa-trash-alt');
                } else if(variant === 'warning') {
                    icon.classList.add('fa-exclamation-triangle');
                } else if(variant === 'info') {
                    icon.classList.add('fa-info-circle');
                } else if(variant === 'success') {
                    icon.classList.add('fa-check-circle');
                } else {
                    icon.classList.add('fa-question-circle');
                }
                
                // reset loading state
                submitBtn.disabled = false;
                document.getElementById('actionModalCancelBtn').disabled = false;
                document.getElementById('actionModalSpinner').classList.add('d-none');
            });
            
            document.getElementById('actionModalSubmitBtn').addEventListener('click', function() {
                if(currentTargetForm) {
                    this.disabled = true;
                    document.getElementById('actionModalCancelBtn').disabled = true;
                    document.getElementById('actionModalSpinner').classList.remove('d-none');
                    currentTargetForm.submit();
                }
            });
        }
    });
</script>
