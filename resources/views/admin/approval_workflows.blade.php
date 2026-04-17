@extends('layouts.admin')

@section('title', 'Alur Persetujuan (Workflow)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Alur Persetujuan Cuti</h4>
        <p class="text-secondary small mb-0">Konfigurasi tahapan persetujuan berdasarkan departemen atau struktur organisasi.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#workflowModal" id="addWorkflowBtn">
        <i class="ti ti-plus me-1"></i> Buat Alur Baru
    </button>
</div>

<div class="row g-4" id="workflowContainer">
    <!-- Workflows will be loaded here -->
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary"></div>
    </div>
</div>

<!-- Modal Create/Edit Workflow -->
<div class="modal fade" id="workflowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom shadow-none">
                <h5 class="modal-title fw-bold" id="workflowModalTitle">Buat Alur Persetujuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="workflowForm">
                    <input type="hidden" name="id" id="workflowId">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Alur</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Alur Kantor" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Penjelasan singkat..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold text-primary mb-0">TAHAPAN PERSETUJUAN</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addStepBtn">
                            <i class="ti ti-plus me-1"></i> Tambah Tahap
                        </button>
                    </div>
                    
                    <div id="stepsContainer" class="bg-light p-3 rounded-3 border">
                        <!-- Steps injected here -->
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Alur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
    import { api } from "{{ asset('admin-assets/js/api.js') }}";
    
    const container = document.getElementById('workflowContainer');
    const stepsContainer = document.getElementById('stepsContainer');
    const workflowForm = document.getElementById('workflowForm');
    const modalEl = document.getElementById('workflowModal');
    const bsModal = new bootstrap.Modal(modalEl);

    async function loadWorkflows() {
        try {
            const res = await api.request('/admin/approval-workflows');
            if (res.success) renderWorkflows(res.data);
        } catch (e) {
            container.innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
        }
    }

    function renderWorkflows(data) {
        if (data.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-secondary py-5">Belum ada alur persetujuan.</div>';
            return;
        }

        container.innerHTML = data.map(wf => `
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary-subtle text-primary border-primary">ID: ${wf.id}</span>
                            <div class="dropdown">
                                <i class="ti ti-dots-vertical cursor-pointer" data-bs-toggle="dropdown"></i>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item small edit-btn" href="#" data-id="${wf.id}">Edit</a></li>
                                    <li><a class="dropdown-item small text-danger delete-btn" href="#" data-id="${wf.id}">Hapus</a></li>
                                </ul>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-1">${wf.name}</h5>
                        <p class="text-secondary x-small mb-4">${wf.description || '-'}</p>
                        
                        <label class="x-small fw-bold text-uppercase text-secondary mb-2" style="font-size: 10px;">Tahapan Persetujuan:</label>
                        <div class="workflow-steps position-relative ps-3">
                            ${wf.steps.map((step, i) => `
                                <div class="step-item pb-3 position-relative">
                                    <div class="step-circle bg-white border border-primary text-primary small d-flex align-items-center justify-content-center" 
                                         style="width: 20px; height: 20px; position: absolute; left: -24px; top: 2px; z-index: 2; border-radius: 50%; font-size: 10px; font-weight: bold;">
                                        ${step.step_number}
                                    </div>
                                    <div class="fw-bold small text-capitalize text-dark">${step.approver_type}</div>
                                    <div class="x-small text-secondary">${step.is_final ? '<span class="text-success fw-bold">Persetujuan Final (Approved)</span>' : 'Menuju tahap selanjutnya'}</div>
                                    ${i < wf.steps.length - 1 ? '<div class="step-line bg-light" style="width: 2px; position: absolute; left: -15px; top: 22px; bottom: 0; z-index: 1;"></div>' : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        // Listen for Edit/Delete
        container.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                editWorkflow(btn.dataset.id);
            });
        });

        container.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (confirm('Hapus alur persetujuan ini?')) {
                    try {
                        const res = await api.request(`/admin/approval-workflows/${btn.dataset.id}`, { method: 'DELETE' });
                        if (res.success) {
                            api.notify('Alur berhasil dihapus');
                            loadWorkflows();
                        }
                    } catch (err) { api.notify(err.message, 'danger'); }
                }
            });
        });
    }

    function addStepRow(data = null) {
        const stepNum = stepsContainer.children.length + 1;
        const div = document.createElement('div');
        div.className = 'step-row bg-white rounded border p-2 mb-2 d-flex align-items-center gap-2';
        div.innerHTML = `
            <span class="fw-bold text-primary px-2">${stepNum}</span>
            <select class="form-select form-select-sm approver-type-select" name="steps[${stepNum-1}][approver_type]" required>
                <option value="spv" ${data?.approver_type === 'spv' ? 'selected' : ''}>Supervisor / Manager</option>
                <option value="hr" ${data?.approver_type === 'hr' ? 'selected' : ''}>HR / Personnel</option>
            </select>
            <div class="form-check form-check-inline mb-0 x-small flex-shrink-0">
                <input class="form-check-input final-step-check" type="checkbox" name="steps[${stepNum-1}][is_final]" ${data?.is_final ? 'checked' : ''}>
                <label class="form-check-label small">Final?</label>
            </div>
            <button type="button" class="btn btn-sm btn-light text-danger remove-step-btn"><i class="ti ti-x"></i></button>
        `;
        stepsContainer.appendChild(div);

        div.querySelector('.remove-step-btn').addEventListener('click', () => {
            div.remove();
            renumberSteps();
        });
    }

    function renumberSteps() {
        Array.from(stepsContainer.children).forEach((child, i) => {
            child.querySelector('span').innerText = i + 1;
            child.querySelector('.approver-type-select').name = `steps[${i}][approver_type]`;
            child.querySelector('.final-step-check').name = `steps[${i}][is_final]`;
        });
    }

    async function editWorkflow(id) {
        try {
            const res = await api.request(`/admin/approval-workflows/${id}`); // Assuming details endpoint exists
            if (res.success) {
                const wf = res.data;
                document.getElementById('workflowId').value = wf.id;
                document.getElementById('workflowModalTitle').innerText = 'Ubah Alur Persetujuan';
                workflowForm.querySelector('[name="name"]').value = wf.name;
                workflowForm.querySelector('[name="description"]').value = wf.description || '';
                
                stepsContainer.innerHTML = '';
                wf.steps.forEach(s => addStepRow(s));
                bsModal.show();
            }
        } catch (e) { api.notify(e.message, 'danger'); }
    }

    document.getElementById('addStepBtn').addEventListener('click', () => addStepRow());

    workflowForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(workflowForm);
        const id = document.getElementById('workflowId').value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `/admin/approval-workflows/${id}` : '/admin/approval-workflows';

        // Custom handling for checkboxes because FormData/Object.fromEntries skips unchecked
        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            steps: []
        };
        
        Array.from(stepsContainer.children).forEach((child, i) => {
            data.steps.push({
                step_number: i + 1,
                approver_type: child.querySelector('.approver-type-select').value,
                is_final: child.querySelector('.final-step-check').checked ? 1 : 0
            });
        });

        try {
            const res = await api.request(url, {
                method: method,
                body: JSON.stringify(data)
            });
            if (res.success) {
                api.notify('Alur berhasil disimpan');
                bsModal.hide();
                loadWorkflows();
            }
        } catch (err) { api.notify(err.message, 'danger'); }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        workflowForm.reset();
        document.getElementById('workflowId').value = '';
        document.getElementById('workflowModalTitle').innerText = 'Buat Alur Persetujuan';
        stepsContainer.innerHTML = '';
        addStepRow(); // Start with 1 step
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadWorkflows();
        addStepRow();
    });
</script>
@endpush
