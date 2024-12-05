<div id="addVaccinationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Vaccination Record</h2>
            <span class="close" data-modal="addVaccinationModal">&times;</span>
        </div>
        <form id="addVaccinationForm" method="POST" action="process-add-vaccination.php">
            <div class="form-group">
                <label for="pet_id">Select Pet</label>
                <select id="pet_id" name="pet_id" required>
                    <?php while($pet = $pets->fetch_assoc()): ?>
                        <option value="<?php echo $pet['pet_id']; ?>">
                            <?php echo htmlspecialchars($pet['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="vaccine_name">Vaccine Name</label>
                <input type="text" id="vaccine_name" name="vaccine_name" required>
            </div>
            <div class="form-group">
                <label for="vaccination_date">Vaccination Date</label>
                <input type="date" id="vaccination_date" name="vaccination_date" required>
            </div>
            <div class="form-group">
                <label for="next_due_date">Next Due Date</label>
                <input type="date" id="next_due_date" name="next_due_date">
            </div>
            <div class="form-group">
                <label for="batch_number">Batch Number</label>
            <div class="input-group">
                <input type="text" id="batch_number" name="batch_number">
                    <button type="button" class="btn btn-secondary" onclick="generateBatchNumber()">
                        <i class="fas fa-random"></i> Generate
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Add Record</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.active {
    opacity: 1;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px);
    transition: transform 0.3s ease;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal.active .modal-content {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #E5E7EB;
}

.modal-header h2 {
    color: #1F2937;
    font-size: 1.5rem;
    font-weight: 600;
}

.close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #6B7280;
    transition: all 0.2s;
    background: #F3F4F6;
}

.close:hover {
    background: #E5E7EB;
    color: #1F2937;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #374151;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
    background: #F9FAFB;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #8B5CF6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
}

.modal-footer {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 2px solid #E5E7EB;
    display: flex;
    justify-content: flex-end;
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.btn-secondary {
    background: #6B7280;
    color: white;
}

.btn-secondary:hover {
    background: #4B5563;
}

</style>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
    requestAnimationFrame(() => {
        modal.classList.add('active');
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

document.querySelectorAll('.close').forEach(button => {
    button.addEventListener('click', function() {
        const modalId = this.getAttribute('data-modal');
        closeModal(modalId);
    });
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}

document.getElementById('addVaccinationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('process-add-vaccination.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('addVaccinationModal');
            location.reload();
        } else {
            alert(data.message);
        }
    });
});

function generateBatchNumber() {
    const prefix = 'VAX';
    const timestamp = Date.now().toString().slice(-6);
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    const batchNumber = `${prefix}-${timestamp}-${random}`;
    document.getElementById('batch_number').value = batchNumber;
}

</script>
