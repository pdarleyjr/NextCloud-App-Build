<template>
  <div class="schedule-view">
    <h2>{{ t('appointments', 'Your Schedule') }}</h2>
    
    <div class="schedule-controls">
      <div class="date-navigation">
        <button class="icon-previous" @click="previousWeek" :title="t('appointments', 'Previous Week')"></button>
        <span class="current-week">{{ formatDateRange(weekStart, weekEnd) }}</span>
        <button class="icon-next" @click="nextWeek" :title="t('appointments', 'Next Week')"></button>
      </div>
      
      <button class="primary" @click="showAvailabilityModal = true">
        {{ t('appointments', 'Set Availability') }}
      </button>
    </div>
    
    <div class="schedule-grid">
      <div class="time-column">
        <div class="day-header"></div>
        <div v-for="hour in hours" :key="hour" class="time-slot">
          {{ formatHour(hour) }}
        </div>
      </div>
      
      <div v-for="day in days" :key="day.date" class="day-column">
        <div class="day-header">
          <div class="day-name">{{ day.name }}</div>
          <div class="day-date">{{ formatDay(day.date) }}</div>
        </div>
        
        <div 
          v-for="hour in hours" 
          :key="`${day.date}-${hour}`" 
          class="schedule-cell"
          :class="{ 
            'available': isAvailable(day.date, hour),
            'appointment': hasAppointment(day.date, hour)
          }"
          @click="toggleAvailability(day.date, hour)"
        >
          <div v-if="getAppointment(day.date, hour)" class="appointment-info">
            <div class="appointment-title">{{ getAppointment(day.date, hour).title }}</div>
            <div class="appointment-time">
              {{ formatTime(getAppointment(day.date, hour).startTime) }} - 
              {{ formatTime(getAppointment(day.date, hour).endTime) }}
            </div>
            <div class="appointment-client">{{ getAppointment(day.date, hour).clientName }}</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Availability Modal -->
    <div v-if="showAvailabilityModal" class="modal-backdrop" @click="showAvailabilityModal = false">
      <div class="modal-content" @click.stop>
        <h3>{{ t('appointments', 'Set Weekly Availability') }}</h3>
        
        <div class="availability-form">
          <div v-for="(day, index) in weekdays" :key="day" class="availability-day">
            <label>
              <input type="checkbox" v-model="availability[index].enabled">
              {{ day }}
            </label>
            
            <div v-if="availability[index].enabled" class="time-range">
              <select v-model="availability[index].start">
                <option v-for="time in timeOptions" :key="time.value" :value="time.value">
                  {{ time.label }}
                </option>
              </select>
              <span>{{ t('appointments', 'to') }}</span>
              <select v-model="availability[index].end">
                <option v-for="time in timeOptions" :key="time.value" :value="time.value">
                  {{ time.label }}
                </option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="modal-actions">
          <button class="primary" @click="saveAvailability">
            {{ t('appointments', 'Save') }}
          </button>
          <button @click="showAvailabilityModal = false">
            {{ t('appointments', 'Cancel') }}
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
  name: 'Schedule',
  
  data() {
    return {
      weekStart: new Date(),
      weekEnd: new Date(),
      hours: Array.from({ length: 12 }, (_, i) => i + 8), // 8am to 7pm
      days: [],
      appointments: [],
      availabilityData: {},
      showAvailabilityModal: false,
      weekdays: [
        t('appointments', 'Monday'),
        t('appointments', 'Tuesday'),
        t('appointments', 'Wednesday'),
        t('appointments', 'Thursday'),
        t('appointments', 'Friday'),
        t('appointments', 'Saturday'),
        t('appointments', 'Sunday')
      ],
      availability: [
        { enabled: true, start: '09:00', end: '17:00' },
        { enabled: true, start: '09:00', end: '17:00' },
        { enabled: true, start: '09:00', end: '17:00' },
        { enabled: true, start: '09:00', end: '17:00' },
        { enabled: true, start: '09:00', end: '17:00' },
        { enabled: false, start: '09:00', end: '17:00' },
        { enabled: false, start: '09:00', end: '17:00' }
      ],
      timeOptions: [
        { value: '08:00', label: '8:00 AM' },
        { value: '09:00', label: '9:00 AM' },
        { value: '10:00', label: '10:00 AM' },
        { value: '11:00', label: '11:00 AM' },
        { value: '12:00', label: '12:00 PM' },
        { value: '13:00', label: '1:00 PM' },
        { value: '14:00', label: '2:00 PM' },
        { value: '15:00', label: '3:00 PM' },
        { value: '16:00', label: '4:00 PM' },
        { value: '17:00', label: '5:00 PM' },
        { value: '18:00', label: '6:00 PM' },
        { value: '19:00', label: '7:00 PM' }
      ]
    };
  },
  
  mounted() {
    this.initializeWeek();
    this.loadAppointments();
    this.loadAvailability();
  },
  
  methods: {
    t,
    
    initializeWeek() {
      // Set week to start on Monday of current week
      const today = new Date();
      const dayOfWeek = today.getDay();
      const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
      
      this.weekStart = new Date(today.setDate(diff));
      this.weekEnd = new Date(this.weekStart);
      this.weekEnd.setDate(this.weekEnd.getDate() + 6);
      
      this.generateDays();
    },
    
    generateDays() {
      this.days = [];
      const dayNames = [
        t('appointments', 'Mon'),
        t('appointments', 'Tue'),
        t('appointments', 'Wed'),
        t('appointments', 'Thu'),
        t('appointments', 'Fri'),
        t('appointments', 'Sat'),
        t('appointments', 'Sun')
      ];
      
      for (let i = 0; i < 7; i++) {
        const date = new Date(this.weekStart);
        date.setDate(date.getDate() + i);
        
        this.days.push({
          name: dayNames[i],
          date: date.toISOString().split('T')[0]
        });
      }
    },
    
    previousWeek() {
      this.weekStart.setDate(this.weekStart.getDate() - 7);
      this.weekEnd.setDate(this.weekEnd.getDate() - 7);
      this.generateDays();
      this.loadAppointments();
    },
    
    nextWeek() {
      this.weekStart.setDate(this.weekStart.getDate() + 7);
      this.weekEnd.setDate(this.weekEnd.getDate() + 7);
      this.generateDays();
      this.loadAppointments();
    },
    
    formatDateRange(start, end) {
      const options = { month: 'short', day: 'numeric' };
      return `${start.toLocaleDateString(undefined, options)} - ${end.toLocaleDateString(undefined, options)}`;
    },
    
    formatDay(dateString) {
      const date = new Date(dateString);
      return date.getDate();
    },
    
    formatHour(hour) {
      return hour < 12 ? `${hour}:00 AM` : hour === 12 ? `12:00 PM` : `${hour - 12}:00 PM`;
    },
    
    formatTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    loadAppointments() {
      const startDate = this.weekStart.toISOString().split('T')[0];
      const endDate = this.weekEnd.toISOString().split('T')[0];
      
      axios.get(generateUrl(`/apps/appointments/api/therapists/${this.$root.$data.userId}/schedule`), {
        params: { start: startDate, end: endDate }
      })
        .then(response => {
          this.appointments = response.data;
        })
        .catch(error => {
          console.error('Error loading appointments:', error);
          showError(t('appointments', 'Failed to load schedule'));
        });
    },
    
    loadAvailability() {
      axios.get(generateUrl(`/apps/appointments/api/therapists/${this.$root.$data.userId}/schedule`))
        .then(response => {
          this.availabilityData = response.data.availability || {};
          
          // Update the availability form data
          if (this.availabilityData.weekly) {
            this.availability = this.availabilityData.weekly.map(day => ({
              enabled: day.enabled,
              start: day.start,
              end: day.end
            }));
          }
        })
        .catch(error => {
          console.error('Error loading availability:', error);
        });
    },
    
    saveAvailability() {
      axios.put(generateUrl(`/apps/appointments/api/therapists/${this.$root.$data.userId}/schedule`), {
        availability: {
          weekly: this.availability
        }
      })
        .then(() => {
          showSuccess(t('appointments', 'Availability saved successfully'));
          this.showAvailabilityModal = false;
          this.loadAvailability();
        })
        .catch(error => {
          console.error('Error saving availability:', error);
          showError(t('appointments', 'Failed to save availability'));
        });
    },
    
    isAvailable(date, hour) {
      const dayOfWeek = new Date(date).getDay();
      const adjustedDay = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Convert to 0-6 where 0 is Monday
      
      if (!this.availabilityData.weekly || !this.availabilityData.weekly[adjustedDay].enabled) {
        return false;
      }
      
      const startHour = parseInt(this.availabilityData.weekly[adjustedDay].start.split(':')[0]);
      const endHour = parseInt(this.availabilityData.weekly[adjustedDay].end.split(':')[0]);
      
      return hour >= startHour && hour < endHour;
    },
    
    hasAppointment(date, hour) {
      return this.getAppointment(date, hour) !== null;
    },
    
    getAppointment(date, hour) {
      return this.appointments.find(appointment => {
        const appointmentDate = new Date(appointment.startTime).toISOString().split('T')[0];
        const appointmentHour = new Date(appointment.startTime).getHours();
        
        return appointmentDate === date && appointmentHour === hour;
      }) || null;
    },
    
    toggleAvailability(date, hour) {
      const appointment = this.getAppointment(date, hour);
      
      if (appointment) {
        // If there's an appointment, show details or actions
        // This could open a modal with appointment details
        console.log('Appointment details:', appointment);
      } else {
        // Toggle availability for this slot
        // This would be implemented with a proper API call in a real app
        console.log('Toggle availability for', date, hour);
      }
    }
  }
};
</script>

<style scoped>
.schedule-view {
  max-width: 1200px;
  margin: 0 auto;
}

.schedule-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 20px 0;
}

.date-navigation {
  display: flex;
  align-items: center;
  gap: 10px;
}

.current-week {
  font-weight: bold;
  min-width: 150px;
  text-align: center;
}

.schedule-grid {
  display: flex;
  border: 1px solid var(--color-border);
  border-radius: 5px;
  overflow: hidden;
}

.time-column, .day-column {
  flex: 1;
  min-width: 100px;
}

.time-column {
  max-width: 80px;
  background-color: var(--color-background-dark);
}

.day-header {
  height: 60px;
  padding: 10px;
  text-align: center;
  border-bottom: 1px solid var(--color-border);
  background-color: var(--color-background-dark);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.day-name {
  font-weight: bold;
}

.day-date {
  font-size: 1.2em;
  margin-top: 5px;
}

.time-slot {
  height: 60px;
  padding: 10px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9em;
}

.schedule-cell {
  height: 60px;
  border-bottom: 1px solid var(--color-border);
  border-left: 1px solid var(--color-border);
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
}

.schedule-cell:hover {
  background-color: var(--color-background-hover);
}

.available {
  background-color: rgba(var(--color-success-rgb), 0.1);
}

.appointment {
  background-color: var(--color-primary-element-light);
  cursor: default;
}

.appointment-info {
  padding: 5px;
  font-size: 0.8em;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.appointment-title {
  font-weight: bold;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.appointment-time, .appointment-client {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Modal styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9000;
}

.modal-content {
  background-color: var(--color-main-background);
  border-radius: 5px;
  padding: 20px;
  width: 500px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.availability-form {
  margin: 20px 0;
}

.availability-day {
  margin-bottom: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.time-range {
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}
</style>