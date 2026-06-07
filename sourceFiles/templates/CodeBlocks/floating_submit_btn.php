/****************** HTML ******************/
<div class="saveAndSubmitButton">

    <div class="btn-group card-option">
        <?= $this->Form->button('Save', ['name' => 'SUBMIT_PRESSED', 'class' => 'btn btn-primary btn-block']) ?>
        <?= $this->Html->link('Cancel', ['prefix' => 'Staff', 'action' => 'index'],['class' => 'btn btn-warning']) ?>
    </div>

</div>

<div id="saveButtonSentinel"></div>





/****************** PUT in footer of layout ******************/
<style>
    .saveAndSubmitButton {
        transition: all 0.3s ease;
    }
    .saveAndSubmitButton.float {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        background: white;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    #saveButtonSentinel {
        height: 1px; /* invisible marker to track position */
    }
</style>
<script>
    const buttonContainer = document.querySelector('.saveAndSubmitButton');
    const sentinel = document.getElementById('saveButtonSentinel');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) {
                buttonContainer.classList.add('float');
            } else {
                buttonContainer.classList.remove('float');
            }
        });
    });
    // Observe the sentinel (not the button container)
    observer.observe(sentinel);
</script>

