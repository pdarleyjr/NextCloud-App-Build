<template>
  <div class="appointments-list">
    <h2>{{ t('appointments', 'Your Appointments') }}</h2>
    
    <div v-if="loading" class="loading">
      <div class="icon-loading"></div>
      <span>{{ t('appointments', 'Loading appointments...') }}</span>
    </div>
    
    <div v-else-if="error" class="empty-content">
      <div class="icon-error"></div>
      <h2>{{ t('appointments', 'Error loading appointments') }}</h2>
      <p>{{ error }}</p>
    </div>
    
    <div v-else-if="appointments.length === 0" class="empty-content">
      <div class="icon-calendar"></div>
      <h2>{{ t('appointments', 'No appointments found') }}</h2>
      <p v-if="!isTherapist">
        {{ t('appointments', 'You have no upcoming appointments. Click the button below to book one.') }}
        <div class="empty-content__action">
          <button class="primary" @click="bookAppointment">
            {{ t('appointments', 'Book Appointment') }}
          </button>
        </div>
      </p>
      <p v-else>
        {{ t('appointments', 'You have no upcoming appointments with clients.') }}
      </p>
    </div>
    
    <div v-else class="appointments-grid">
      <div v-for="appointment in appointments" :key="appointment.id" class="appointment-card">
        <div class="appointment-card__header">
          <div class="appointment-card__title">{{ appointment.title }}</div>
          <div class="appointment-card__status" :class="'status-' + appointment.status">
            {{ appointment.status }}
          </div>
        </div>
        
        <div class="appointment-card__body">
          <div class="appointment-card__detail">
            <span class="icon-calendar"></span>
            <span>{{ formatDate(appointment.startTime) }}</span>
          </div>
          
          <div class="appointment-card__detail">
            <span class="icon-time"></span>
            <span>{{ formatTime(appointment.startTime) }} - {{ formatTime(appointment.endTime) }}</span>
          </div>
          
          <div class="appointment-card__detail">
            <span class="icon-user"></span>
            <span v-if="isTherapist">{{ t('appointments', 'Client') }}: {{ appointment.clientName }}</span>
            <span v-else>{{ t('appointments', 'Therapist') }}: {{ appointment.therapistName }}</span>
          </div>
          
          <div v-if="appointment.notes" class="appointment-card__notes">
            <div class="notes-label">{{ t('appointments', 'Notes') }}:</div>
            <div class="notes-content">{{ appointment.notes }}</div>
          </div>
        </div>
        
        <div class="appointment-card__actions">
          <button v-if="appointment.status === 'scheduled'" class="error" @click="cancelAppointment(appointment)">
            {{ t('appointments', 'Cancel') }}
          </button>
          
          <button v-if="isTherapist && appointment.status === 'completed'" class="primary" @click="createInvoice(appointment)">
            {{ t('appointments', 'Create Invoice') }}
          </button>
        </div>
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
  name: 'AppointmentsList',
  
  data() {
    return {
      appointments: [],
      loading: true,
      error: null,
      isTherapist: false
    };
  },
  
  mounted() {
    // Get user data from parent app
    if (this.$root.$data) {
      this.isTherapist = this.$root.$data.isTherapist;
    }
    
    this.loadAppointments();
  },
  
  methods: {
    t,
    
    loadAppointments() {
      this.loading = true;
      this.error = null;
      
      axios.get(generateUrl('/apps/appointments/api/appointments'))
        .then(response => {
          this.appointments = response.data;
        })
        .catch(error => {
          console.error('Error loading appointments:', error);
          this.error = t('appointments', 'Failed to load appointments');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    
    formatTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    bookAppointment() {
      this.$router.push({ name: 'book' });
    },
    
    cancelAppointment(appointment) {
      if (confirm(t('appointments', 'Are you sure you want to cancel this appointment?'))) {
        axios.delete(generateUrl(`/apps/appointments/api/appointments/${appointment.id}`))
          .then(() => {
            showSuccess(t('appointments', 'Appointment cancelled successfully'));
            this.loadAppointments();
          })
          .catch(error => {
            console.error('Error cancelling appointment:', error);
            showError(t('appointments', 'Failed to cancel appointment'));
          });
      }
    },
    
    createInvoice(appointment) {
      this.$router.push({ 
        name: 'create-invoice', 
        params: { appointmentId: appointment.id } 
      });
    }
  }
};
</script>

<style scoped>
.appointments-list {
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

.empty-content__action {
  margin-top: 20px;
}

.appointments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.appointment-card {
  background-color: var(--color-background-hover);
  border-radius: 5px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.appointment-card__header {
  padding: 15px;
  background-color: var(--color-primary-element);
  color: var(--color-primary-text);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.appointment-card__title {
  font-weight: bold;
}

.appointment-card__status {
  text-transform: capitalize;
  font-size: 0.9em;
  padding: 3px 8px;
  border-radius: 10px;
  background-color: rgba(255, 255, 255, 0.2);
}

.status-scheduled {
  background-color: var(--color-warning);
  color: var(--color-main-text);
}

.status-completed {
  background-color: var(--color-success);
}

.status-cancelled {
  background-color: var(--color-error);
}

.appointment-card__body {
  padding: 15px;
}

.appointment-card__detail {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
}

.appointment-card__detail span:first-child {
  margin-right: 10px;
  opacity: 0.7;
}

.appointment-card__notes {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid var(--color-border);
}

.notes-label {
  font-weight: bold;
  margin-bottom: 5px;
}

.notes-content {
  white-space: pre-line;
  font-size: 0.9em;
}

.appointment-card__actions {
  padding: 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid var(--color-border);
}
</style>