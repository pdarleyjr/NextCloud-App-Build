<template>
  <div class="book-appointment">
    <h2>{{ t('appointments', 'Book an Appointment') }}</h2>
    
    <div class="book-appointment__description">
      <p>{{ t('appointments', 'Select a therapist, appointment type, and choose a date and time for your appointment.') }}</p>
    </div>
    
    <div v-if="!recentlyBookedAppointment" class="booking-container">
      <TherapistSelector @appointment-booked="onAppointmentBooked" />
    </div>
    
    <div v-else class="appointment-confirmation">
      <h3>{{ t('appointments', 'Appointment Confirmed') }}</h3>
      <div class="appointment-details">
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Therapist') }}:</span>
          <span class="value">{{ recentlyBookedAppointment.therapistName }}</span>
        </div>
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Appointment Type') }}:</span>
          <span class="value">{{ recentlyBookedAppointment.appointmentTypeName }}</span>
        </div>
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Date') }}:</span>
          <span class="value">{{ formatDate(recentlyBookedAppointment.startTime) }}</span>
        </div>
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Time') }}:</span>
          <span class="value">{{ formatTime(recentlyBookedAppointment.startTime) }} - {{ formatTime(recentlyBookedAppointment.endTime) }}</span>
        </div>
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Price') }}:</span>
          <span class="value">${{ recentlyBookedAppointment.price.toFixed(2) }}</span>
        </div>
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Status') }}:</span>
          <span class="value status">{{ recentlyBookedAppointment.status }}</span>
        </div>
      </div>
      
      <div class="appointment-actions">
        <button class="primary" @click="bookAnother">
          {{ t('appointments', 'Book Another Appointment') }}
        </button>
        <button class="secondary" @click="viewAppointments">
          {{ t('appointments', 'View All Appointments') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import TherapistSelector from '../components/TherapistSelector.vue';

export default {
  name: 'BookAppointment',
  
  components: {
    TherapistSelector
  },
  
  data() {
    return {
      recentlyBookedAppointment: null
    };
  },
  
  methods: {
    t,
    
    onAppointmentBooked(appointment) {
      this.recentlyBookedAppointment = appointment;
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    
    formatTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    bookAnother() {
      this.recentlyBookedAppointment = null;
    },
    
    viewAppointments() {
      this.$router.push({ name: 'appointments' });
    }
  }
};
</script>

<style scoped>
.book-appointment {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

.book-appointment h2 {
  margin-bottom: 20px;
  color: var(--color-primary);
}

.book-appointment__description {
  margin-bottom: 30px;
  color: var(--color-text-maxcontrast);
}

.booking-container {
  background-color: var(--color-main-background);
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.appointment-confirmation {
  margin-top: 20px;
  padding: 30px;
  background-color: var(--color-background-hover);
  border-radius: 8px;
  border-left: 4px solid var(--color-success);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.appointment-confirmation h3 {
  margin-top: 0;
  color: var(--color-success);
  font-size: 1.5em;
}

.appointment-details {
  margin: 20px 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 15px;
}

.appointment-details__item {
  display: flex;
  align-items: center;
}

.appointment-details__item .label {
  font-weight: bold;
  width: 150px;
  color: var(--color-text-maxcontrast);
}

.appointment-details__item .value {
  font-weight: 500;
}

.appointment-details__item .status {
  text-transform: capitalize;
  color: var(--color-success);
  font-weight: bold;
}

.appointment-actions {
  margin-top: 30px;
  display: flex;
  gap: 15px;
}

@media (max-width: 768px) {
  .appointment-details {
    grid-template-columns: 1fr;
  }
  
  .appointment-actions {
    flex-direction: column;
  }
  
  .appointment-actions button {
    width: 100%;
  }
}
</style>