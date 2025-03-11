<template>
  <div class="invoices-list">
    <h2>{{ t('appointments', 'Your Invoices') }}</h2>
    
    <div v-if="loading" class="loading">
      <div class="icon-loading"></div>
      <span>{{ t('appointments', 'Loading invoices...') }}</span>
    </div>
    
    <div v-else-if="error" class="empty-content">
      <div class="icon-error"></div>
      <h2>{{ t('appointments', 'Error loading invoices') }}</h2>
      <p>{{ error }}</p>
    </div>
    
    <div v-else-if="invoices.length === 0" class="empty-content">
      <div class="icon-details"></div>
      <h2>{{ t('appointments', 'No invoices found') }}</h2>
      <p>{{ t('appointments', 'You have no invoices yet.') }}</p>
    </div>
    
    <div v-else class="invoices-table">
      <table>
        <thead>
          <tr>
            <th>{{ t('appointments', 'Invoice #') }}</th>
            <th>{{ t('appointments', 'Date') }}</th>
            <th>{{ t('appointments', 'Client') }}</th>
            <th>{{ t('appointments', 'Amount') }}</th>
            <th>{{ t('appointments', 'Status') }}</th>
            <th>{{ t('appointments', 'Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="invoice in invoices" :key="invoice.id">
            <td>{{ invoice.invoiceNumber }}</td>
            <td>{{ formatDate(invoice.date) }}</td>
            <td>{{ invoice.clientName }}</td>
            <td>${{ invoice.amount.toFixed(2) }}</td>
            <td>
              <span class="status-badge" :class="'status-' + invoice.status.toLowerCase()">
                {{ invoice.status }}
              </span>
            </td>
            <td class="actions">
              <button class="icon-download" @click="downloadInvoice(invoice)" :title="t('appointments', 'Download')"></button>
              <button v-if="invoice.status === 'Unpaid'" class="icon-checkmark" @click="markAsPaid(invoice)" :title="t('appointments', 'Mark as Paid')"></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'InvoicesList',
  
  data() {
    return {
      invoices: [],
      loading: true,
      error: null
    };
  },
  
  mounted() {
    this.loadInvoices();
  },
  
  methods: {
    t,
    
    loadInvoices() {
      this.loading = true;
      this.error = null;
      
      axios.get(generateUrl('/apps/appointments/api/invoices'))
        .then(response => {
          this.invoices = response.data;
        })
        .catch(error => {
          console.error('Error loading invoices:', error);
          this.error = t('appointments', 'Failed to load invoices');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    
    downloadInvoice(invoice) {
      window.open(generateUrl(`/apps/appointments/api/invoices/${invoice.id}`), '_blank');
    },
    
    markAsPaid(invoice) {
      axios.put(generateUrl(`/apps/appointments/api/invoices/${invoice.id}`), {
        status: 'Paid'
      })
        .then(() => {
          showSuccess(t('appointments', 'Invoice marked as paid'));
          this.loadInvoices();
        })
        .catch(error => {
          console.error('Error updating invoice:', error);
          showError(t('appointments', 'Failed to update invoice'));
        });
    }
  }
};
</script>

<style scoped>
.invoices-list {
  max-width: 1000px;
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

.invoices-table {
  margin-top: 20px;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

th {
  background-color: var(--color-background-dark);
  font-weight: bold;
}

tr:hover {
  background-color: var(--color-background-hover);
}

.status-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 10px;
  font-size: 0.9em;
  text-transform: capitalize;
}

.status-paid {
  background-color: var(--color-success);
  color: white;
}

.status-unpaid {
  background-color: var(--color-warning);
  color: var(--color-main-text);
}

.actions {
  display: flex;
  gap: 10px;
}

.actions button {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
}

.actions button:hover {
  background-color: var(--color-background-dark);
}
</style>