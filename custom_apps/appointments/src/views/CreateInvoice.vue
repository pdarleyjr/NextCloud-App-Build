<template>
  <div class="create-invoice">
    <h2>{{ t('appointments', 'Create Invoice') }}</h2>
    
    <div v-if="loading" class="loading">
      <div class="icon-loading"></div>
      <span>{{ t('appointments', 'Loading appointment details...') }}</span>
    </div>
    
    <div v-else-if="error" class="empty-content">
      <div class="icon-error"></div>
      <h2>{{ t('appointments', 'Error loading appointment') }}</h2>
      <p>{{ error }}</p>
    </div>
    
    <div v-else class="invoice-form">
      <div class="form-group">
        <label for="client-name">{{ t('appointments', 'Client Name') }}</label>
        <input type="text" id="client-name" v-model="invoice.clientName" readonly>
      </div>
      
      <div class="form-group">
        <label for="appointment-date">{{ t('appointments', 'Appointment Date') }}</label>
        <input type="text" id="appointment-date" :value="formatDate(appointment.startTime)" readonly>
      </div>
      
      <div class="form-group">
        <label for="appointment-time">{{ t('appointments', 'Appointment Time') }}</label>
        <input type="text" id="appointment-time" :value="formatTime(appointment.startTime) + ' - ' + formatTime(appointment.endTime)" readonly>
      </div>
      
      <div class="form-group">
        <label for="service-type">{{ t('appointments', 'Service Type') }}</label>
        <input type="text" id="service-type" v-model="invoice.serviceType">
      </div>
      
      <div class="form-group">
        <label for="amount">{{ t('appointments', 'Amount ($)') }}</label>
        <input type="number" id="amount" v-model.number="invoice.amount" min="0" step="0.01">
      </div>
      
      <div class="form-group">
        <label for="notes">{{ t('appointments', 'Notes') }}</label>
        <textarea id="notes" v-model="invoice.notes" rows="4"></textarea>
      </div>
      
      <div class="form-actions">
        <button class="primary" @click="saveInvoice" :disabled="submitting">
          <span v-if="submitting">{{ t('appointments', 'Saving...') }}</span>
          <span v-else>{{ t('appointments', 'Save Invoice') }}</span>
        </button>
        <button class="cancel" @click="cancel">{{ t('appointments', 'Cancel') }}</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'CreateInvoice',
  
  props: {
    appointmentId: {
      type: String,
      required: true
    }
  },
  
  data() {
    return {
      appointment: {},
      invoice: {
        clientName: '',
        serviceType: '',
        amount: 0,
        notes: ''
      },
      loading: true,
      submitting: false,
      error: null
    };
  },
  
  mounted() {
    this.loadAppointment();
  },
  
  methods: {
    t,
    
    loadAppointment() {
      this.loading = true;
      this.error = null;
      
      axios.get(generateUrl(`/apps/appointments/api/appointments/${this.appointmentId}`))
        .then(response => {
          this.appointment = response.data;
          this.invoice.clientName = this.appointment.clientName;
          this.invoice.serviceType = this.appointment.title || 'Therapy Session';
        })
        .catch(error => {
          console.error('Error loading appointment:', error);
          this.error = t('appointments', 'Failed to load appointment details');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    
    formatTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    saveInvoice() {
      if (!this.invoice.amount || this.invoice.amount <= 0) {
        showError(t('appointments', 'Please enter a valid amount'));
        return;
      }
      
      this.submitting = true;
      
      const invoiceData = {
        appointmentId: this.appointmentId,
        clientName: this.invoice.clientName,
        serviceType: this.invoice.serviceType,
        amount: this.invoice.amount,
        notes: this.invoice.notes,
        date: new Date().toISOString()
      };
      
      axios.post(generateUrl('/apps/appointments/api/invoices'), invoiceData)
        .then(response => {
          showSuccess(t('appointments', 'Invoice created successfully'));
          this.$router.push({ name: 'invoices' });
        })
        .catch(error => {
          console.error('Error creating invoice:', error);
          showError(t('appointments', 'Failed to create invoice'));
        })
        .finally(() => {
          this.submitting = false;
        });
    },
    
    cancel() {
      this.$router.go(-1);
    }
  }
};
</script>

<style scoped>
.create-invoice {
  max-width: 800px;
  margin: 0 auto;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.empty-content {
  text-align: center;
  padding: 40px 0;
}

.invoice-form {
  margin-top: 20px;
  background-color: var(--color-background-hover);
  padding: 20px;
  border-radius: 5px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

input, textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid var(--color-border);
  border-radius: 3px;
  background-color: var(--color-main-background);
}

input:read-only {
  background-color: var(--color-background-dark);
}

.form-actions {
  margin-top: 20px;
  display: flex;
  gap: 10px;
}

button {
  padding: 8px 16px;
  border-radius: 3px;
  cursor: pointer;
}

button.primary {
  background-color: var(--color-primary);
  color: var(--color-primary-text);
  border: none;
}

button.primary:hover {
  background-color: var(--color-primary-element-light);
}

button.primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

button.cancel {
  background-color: transparent;
  border: 1px solid var(--color-border);
}

button.cancel:hover {
  background-color: var(--color-background-dark);
}
</style>