<template>
  <div class="therapist-selector">
    <div class="therapist-selector__label">{{ t('appointments', 'Select Therapist') }}</div>
    <select v-model="selectedTherapist" class="therapist-selector__dropdown" @change="onTherapistChange">
      <option v-for="therapist in therapists" :key="therapist.id" :value="therapist.id">
        {{ therapist.displayName }}
      </option>
    </select>
    
    <div v-if="selectedTherapistData" class="therapist-details">
      <div class="therapist-details__bio" v-if="selectedTherapistData.bio">
        <h3>{{ t('appointments', 'About') }}</h3>
        <p>{{ selectedTherapistData.bio }}</p>
      </div>
      
      <div class="therapist-details__specialties" v-if="selectedTherapistData.specialties && selectedTherapistData.specialties.length > 0">
        <h3>{{ t('appointments', 'Specialties') }}</h3>
        <ul>
          <li v-for="(specialty, index) in selectedTherapistData.specialties" :key="index">
            {{ specialty }}
          </li>
        </ul>
      </div>
      
      <div class="therapist-details__rate" v-if="selectedTherapistData.hourlyRate">
        <h3>{{ t('appointments', 'Hourly Rate') }}</h3>
        <p>${{ selectedTherapistData.hourlyRate.toFixed(2) }}</p>
      </div>
    </div>
    
    <div v-if="selectedTherapist" class="date-time-selector">
      <div class="date-time-selector__label">{{ t('appointments', 'Select Date & Time') }}</div>
      <input type="datetime-local" v-model="appointmentDateTime" class="date-time-selector__input" />
    </div>
    
    <button 
      v-if="selectedTherapist && appointmentDateTime" 
      @click="bookAppointment" 
      class="primary book-button"
      :disabled="isBooking"
    >
      {{ isBooking ? t('appointments', 'Booking...') : t('appointments', 'Book Appointment') }}
    </button>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'TherapistSelector',
  
  data() {
    return {
      therapists: [],
      selectedTherapist: null,
      selectedTherapistData: null,
      appointmentDateTime: '',
      isBooking: false
    };
  },
  
  mounted() {
    this.loadTherapists();
  },
  
  methods: {
    t,
    
    loadTherapists() {
      axios.get(generateUrl('/apps/appointments/api/therapists'))
        .then(response => {
          this.therapists = response.data;
        })
        .catch(error => {
          console.error('Error loading therapists:', error);
          showError(t('appointments', 'Failed to load therapists'));
        });
    },
    
    onTherapistChange() {
      if (this.selectedTherapist) {
        axios.get(generateUrl(`/apps/appointments/api/therapists/${this.selectedTherapist}`))
          .then(response => {
            this.selectedTherapistData = response.data;
          })
          .catch(error => {
            console.error('Error loading therapist details:', error);
            showError(t('appointments', 'Failed to load therapist details'));
          });
      } else {
        this.selectedTherapistData = null;
      }
    },
    
    bookAppointment() {
      if (!this.selectedTherapist || !this.appointmentDateTime) {
        showError(t('appointments', 'Please select a therapist and appointment time'));
        return;
      }
      
      this.isBooking = true;
      
      // Calculate end time (1 hour after start time)
      const startTime = new Date(this.appointmentDateTime);
      const endTime = new Date(startTime.getTime() + 60 * 60 * 1000); // 1 hour later
      
      axios.post(generateUrl('/apps/appointments/api/appointments'), {
        therapistId: this.selectedTherapist,
        startTime: startTime.toISOString(),
        endTime: endTime.toISOString(),
        title: 'Therapy Session'
      })
        .then(response => {
          showSuccess(t('appointments', 'Appointment booked successfully!'));
          this.$emit('appointment-booked', response.data);
          
          // Reset form
          this.selectedTherapist = null;
          this.selectedTherapistData = null;
          this.appointmentDateTime = '';
        })
        .catch(error => {
          console.error('Error booking appointment:', error);
          showError(t('appointments', 'Failed to book appointment'));
        })
        .finally(() => {
          this.isBooking = false;
        });
    }
  }
};
</script>

<style scoped>
.therapist-selector {
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-width: 600px;
  margin: 0 auto;
}

.therapist-selector__label,
.date-time-selector__label {
  font-weight: bold;
  margin-bottom: 5px;
}

.therapist-selector__dropdown,
.date-time-selector__input {
  width: 100%;
  padding: 8px;
  border-radius: 3px;
  border: 1px solid var(--color-border);
}

.therapist-details {
  background-color: var(--color-background-hover);
  border-radius: 3px;
  padding: 15px;
  margin-top: 10px;
}

.therapist-details h3 {
  margin-top: 0;
  margin-bottom: 10px;
  font-size: 16px;
}

.therapist-details__specialties ul {
  margin: 0;
  padding-left: 20px;
}

.book-button {
  align-self: flex-start;
  margin-top: 10px;
}
</style>