<template>
  <div class="therapist-selector">
    <div class="booking-steps">
      <div class="booking-step" :class="{ 'active': currentStep >= 1, 'completed': currentStep > 1 }">
        <div class="booking-step__number">1</div>
        <div class="booking-step__label">{{ t('appointments', 'Select Therapist') }}</div>
      </div>
      <div class="booking-step" :class="{ 'active': currentStep >= 2, 'completed': currentStep > 2 }">
        <div class="booking-step__number">2</div>
        <div class="booking-step__label">{{ t('appointments', 'Select Appointment Type') }}</div>
      </div>
      <div class="booking-step" :class="{ 'active': currentStep >= 3, 'completed': currentStep > 3 }">
        <div class="booking-step__number">3</div>
        <div class="booking-step__label">{{ t('appointments', 'Select Date & Time') }}</div>
      </div>
      <div class="booking-step" :class="{ 'active': currentStep >= 4, 'completed': currentStep > 4 }">
        <div class="booking-step__number">4</div>
        <div class="booking-step__label">{{ t('appointments', 'Payment') }}</div>
      </div>
    </div>
    
    <!-- Step 1: Therapist Selection -->
    <div v-if="currentStep === 1" class="booking-content">
      <div class="therapist-selector__label">{{ t('appointments', 'Select Therapist') }}</div>
      <select v-model="selectedTherapist" class="therapist-selector__dropdown" @change="onTherapistChange">
        <option value="">{{ t('appointments', 'Please select a therapist') }}</option>
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
      
      <div class="booking-actions">
        <button
          class="primary next-button"
          @click="goToStep(2)"
          :disabled="!selectedTherapist"
        >
          {{ t('appointments', 'Next: Select Appointment Type') }}
        </button>
      </div>
    </div>
    
    <!-- Step 2: Appointment Type Selection -->
    <div v-else-if="currentStep === 2" class="booking-content">
      <AppointmentTypeSelector
        :therapistId="selectedTherapist"
        @appointment-type-selected="onAppointmentTypeSelected"
      />
      
      <div class="booking-actions">
        <button class="back-button" @click="goToStep(1)">
          {{ t('appointments', 'Back') }}
        </button>
        <button
          class="primary next-button"
          @click="goToStep(3)"
          :disabled="!selectedAppointmentType"
        >
          {{ t('appointments', 'Next: Select Date & Time') }}
        </button>
      </div>
    </div>
    
    <!-- Step 3: Date & Time Selection -->
    <div v-else-if="currentStep === 3" class="booking-content">
      <DateTimeSelector
        :therapistId="selectedTherapist"
        :appointmentType="selectedAppointmentTypeData"
        @time-slot-selected="onTimeSlotSelected"
      />
      
      <div class="booking-actions">
        <button class="back-button" @click="goToStep(2)">
          {{ t('appointments', 'Back') }}
        </button>
        <button
          class="primary next-button"
          @click="goToStep(4)"
          :disabled="!selectedTimeSlot"
        >
          {{ t('appointments', 'Next: Payment') }}
        </button>
      </div>
    </div>
    
    <!-- Step 4: Payment -->
    <div v-else-if="currentStep === 4" class="booking-content">
      <PaymentProcessor
        :therapistId="selectedTherapist"
        :appointmentType="selectedAppointmentTypeData"
        :timeSlot="selectedTimeSlot"
        :squareApplicationId="squareApplicationId"
        :squareEnvironment="squareEnvironment"
        @payment-complete="onPaymentComplete"
      />
      
      <div class="booking-actions">
        <button class="back-button" @click="goToStep(3)">
          {{ t('appointments', 'Back') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';
import AppointmentTypeSelector from './AppointmentTypeSelector.vue';
import DateTimeSelector from './DateTimeSelector.vue';
import PaymentProcessor from './PaymentProcessor.vue';

export default {
  name: 'TherapistSelector',
  
  components: {
    AppointmentTypeSelector,
    DateTimeSelector,
    PaymentProcessor
  },
  
  data() {
    return {
      currentStep: 1,
      therapists: [],
      selectedTherapist: '',
      selectedTherapistData: null,
      selectedAppointmentType: null,
      selectedAppointmentTypeData: null,
      selectedTimeSlot: null,
      isBooking: false,
      squareApplicationId: this.$root.squareApplicationId,
      squareEnvironment: this.$root.squareEnvironment
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
      
      // Reset subsequent selections
      this.selectedAppointmentType = null;
      this.selectedAppointmentTypeData = null;
      this.selectedTimeSlot = null;
    },
    
    onAppointmentTypeSelected(appointmentType) {
      this.selectedAppointmentType = appointmentType ? appointmentType.id : null;
      this.selectedAppointmentTypeData = appointmentType;
      
      // Reset subsequent selections
      this.selectedTimeSlot = null;
    },
    
    onTimeSlotSelected(timeSlot) {
      this.selectedTimeSlot = timeSlot;
    },
    
    onPaymentComplete(appointment) {
      this.$emit('appointment-booked', appointment);
    },
    
    goToStep(step) {
      this.currentStep = step;
    },
    
    resetForm() {
      this.currentStep = 1;
      this.selectedTherapist = '';
      this.selectedTherapistData = null;
      this.selectedAppointmentType = null;
      this.selectedAppointmentTypeData = null;
      this.selectedTimeSlot = null;
    }
  }
};
</script>

<style scoped>
.therapist-selector {
  display: flex;
  flex-direction: column;
  gap: 30px;
  max-width: 800px;
  margin: 0 auto;
}

.booking-steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
  position: relative;
}

.booking-steps::before {
  content: '';
  position: absolute;
  top: 20px;
  left: 0;
  right: 0;
  height: 2px;
  background-color: var(--color-border);
  z-index: 1;
}

.booking-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
  flex: 1;
}

.booking-step__number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: var(--color-background-dark);
  color: var(--color-text-maxcontrast);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-bottom: 10px;
  border: 2px solid var(--color-border);
}

.booking-step__label {
  font-size: 0.9em;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.booking-step.active .booking-step__number {
  background-color: var(--color-primary);
  color: var(--color-primary-text);
  border-color: var(--color-primary);
}

.booking-step.active .booking-step__label {
  color: var(--color-text);
  font-weight: bold;
}

.booking-step.completed .booking-step__number {
  background-color: var(--color-success);
  color: var(--color-primary-text);
  border-color: var(--color-success);
}

.booking-content {
  background-color: var(--color-background-hover);
  border-radius: 5px;
  padding: 20px;
}

.therapist-selector__label {
  font-weight: bold;
  margin-bottom: 10px;
}

.therapist-selector__dropdown {
  width: 100%;
  padding: 10px;
  border-radius: 3px;
  border: 1px solid var(--color-border);
  margin-bottom: 20px;
}

.therapist-details {
  background-color: var(--color-background-dark);
  border-radius: 3px;
  padding: 15px;
  margin-top: 10px;
  margin-bottom: 20px;
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

.booking-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}

.back-button,
.next-button {
  padding: 8px 16px;
}

.next-button {
  margin-left: auto;
}
</style>