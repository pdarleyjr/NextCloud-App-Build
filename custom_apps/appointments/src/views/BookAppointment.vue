<template>
  <div class="book-appointment">
    <h2>{{ t('appointments', 'Book an Appointment') }}</h2>
    
    <div class="book-appointment__description">
      <p>{{ t('appointments', 'Select a therapist and choose a date and time for your appointment.') }}</p>
    </div>
    
    <TherapistSelector @appointment-booked="onAppointmentBooked" />
    
    <div v-if="recentlyBookedAppointment" class="appointment-confirmation">
      <h3>{{ t('appointments', 'Appointment Confirmed') }}</h3>
      <div class="appointment-details">
        <div class="appointment-details__item">
          <span class="label">{{ t('appointments', 'Therapist') }}:</span>
          <span class="value">{{ recentlyBookedAppointment.therapistName }}</span>
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
          <span class="label">{{ t('appointments', 'Status') }}:</span>
          <span class="value status">{{ recentlyBookedAppointment.status }}</span>
        </div>
      </div>
      
      <div class="appointment-actions">
        <button class="primary" @click="viewAppointments">
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
    
    viewAppointments() {
      this.$router.push({ name: 'appointments' });
    }
  }
};
</script>

<style scoped>
.book-appointment {
  max-width: 800px;
  margin: 0 auto;
}

.book-appointment h2 {
  margin-bottom: 20px;
}

.book-appointment__description {
  margin-bottom: 30px;
}

.appointment-confirmation {
  margin-top: 40px;
  padding: 20px;
  background-color: var(--color-background-hover);
  border-radius: 5px;
  border-left: 4px solid var(--color-primary);
}

.appointment-confirmation h3 {
  margin-top: 0;
  color: var(--color-primary);
}

.appointment-details {
  margin: 20px 0;
}

.appointment-details__item {
  margin-bottom: 10px;
  display: flex;
}

.appointment-details__item .label {
  font-weight: bold;
  width: 100px;
}

.appointment-details__item .status {
  text-transform: capitalize;
  color: var(--color-success);
}

.appointment-actions {
  margin-top: 20px;
}
</style>