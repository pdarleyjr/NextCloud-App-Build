<template>
  <div class="date-time-selector">
    <div class="date-time-selector__label">{{ t('appointments', 'Select Date & Time') }}</div>
    
    <div v-if="loading" class="date-time-selector__loading">
      <span class="icon-loading"></span>
      {{ t('appointments', 'Loading available time slots...') }}
    </div>
    
    <div v-else-if="error" class="date-time-selector__error">
      {{ error }}
    </div>
    
    <div v-else class="date-time-selector__content">
      <div class="date-selector">
        <div class="date-selector__header">
          <button class="icon-previous" @click="previousWeek"></button>
          <span class="date-range">
            {{ formatDateRange(currentWeekStart, addDays(currentWeekStart, 6)) }}
          </span>
          <button class="icon-next" @click="nextWeek"></button>
        </div>
        
        <div class="date-selector__days">
          <div 
            v-for="(day, index) in weekDays" 
            :key="index"
            class="date-day"
            :class="{ 'active': selectedDate && isSameDay(day, selectedDate) }"
            @click="selectDate(day)"
          >
            <div class="date-day__name">{{ formatDayName(day) }}</div>
            <div class="date-day__date">{{ formatDayDate(day) }}</div>
          </div>
        </div>
      </div>
      
      <div class="time-selector" v-if="selectedDate">
        <div class="time-selector__header">
          {{ t('appointments', 'Available Times for') }} {{ formatFullDate(selectedDate) }}
        </div>
        
        <div v-if="availableTimeSlots.length === 0" class="time-selector__empty">
          {{ t('appointments', 'No available time slots for this date.') }}
        </div>
        
        <div v-else class="time-selector__slots">
          <div 
            v-for="(slot, index) in availableTimeSlots" 
            :key="index"
            class="time-slot"
            :class="{ 'selected': selectedTimeSlot && selectedTimeSlot.startTime === slot.startTime }"
            @click="selectTimeSlot(slot)"
          >
            {{ formatTime(slot.startTime) }} - {{ formatTime(slot.endTime) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { showError } from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'DateTimeSelector',
  
  props: {
    therapistId: {
      type: String,
      required: false,
      default: null
    },
    appointmentType: {
      type: Object,
      required: false,
      default: null
    }
  },
  
  data() {
    return {
      loading: false,
      error: null,
      currentWeekStart: this.getStartOfWeek(new Date()),
      selectedDate: null,
      availableTimeSlots: [],
      selectedTimeSlot: null,
      therapistSchedule: null
    };
  },
  
  computed: {
    weekDays() {
      const days = [];
      const startDate = new Date(this.currentWeekStart);
      
      for (let i = 0; i < 7; i++) {
        const day = new Date(startDate);
        day.setDate(startDate.getDate() + i);
        days.push(day);
      }
      
      return days;
    }
  },
  
  watch: {
    therapistId: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.loadTherapistSchedule();
        } else {
          this.reset();
        }
      }
    },
    
    appointmentType: {
      handler(newVal) {
        if (this.selectedDate && newVal) {
          this.generateTimeSlots();
        }
      }
    }
  },
  
  methods: {
    t,
    
    loadTherapistSchedule() {
      if (!this.therapistId) return;
      
      this.loading = true;
      this.error = null;
      
      axios.get(generateUrl(`/apps/appointments/api/therapists/${this.therapistId}/schedule`))
        .then(response => {
          this.therapistSchedule = response.data;
          
          // Select today by default if it's in the current week
          const today = new Date();
          if (this.isDateInCurrentWeek(today)) {
            this.selectDate(today);
          }
        })
        .catch(error => {
          console.error('Error loading therapist schedule:', error);
          this.error = t('appointments', 'Failed to load therapist schedule');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    generateTimeSlots() {
      if (!this.selectedDate || !this.therapistSchedule || !this.appointmentType) {
        this.availableTimeSlots = [];
        return;
      }
      
      const dayOfWeek = this.getDayOfWeek(this.selectedDate);
      const daySchedule = this.therapistSchedule.find(schedule => schedule.day === dayOfWeek);
      
      if (!daySchedule || !daySchedule.available) {
        this.availableTimeSlots = [];
        return;
      }
      
      // Get existing appointments for this date
      this.loading = true;
      
      const dateString = this.formatDateForApi(this.selectedDate);
      
      axios.get(generateUrl(`/apps/appointments/api/therapists/${this.therapistId}/appointments`), {
        params: { date: dateString }
      })
        .then(response => {
          const existingAppointments = response.data;
          
          // Generate available time slots based on therapist schedule and existing appointments
          const slots = [];
          const startTime = new Date(this.selectedDate);
          const [startHour, startMinute] = daySchedule.startTime.split(':').map(Number);
          startTime.setHours(startHour, startMinute, 0, 0);
          
          const endTime = new Date(this.selectedDate);
          const [endHour, endMinute] = daySchedule.endTime.split(':').map(Number);
          endTime.setHours(endHour, endMinute, 0, 0);
          
          // Calculate total appointment duration including buffers
          const totalDuration = this.appointmentType.duration + 
                               this.appointmentType.packageBefore + 
                               this.appointmentType.packageAfter;
          
          // Generate slots
          const currentTime = new Date(startTime);
          while (currentTime.getTime() + (totalDuration * 60 * 1000) <= endTime.getTime()) {
            const slotEndTime = new Date(currentTime.getTime() + (this.appointmentType.duration * 60 * 1000));
            
            // Check if this slot overlaps with any existing appointment
            const isOverlapping = existingAppointments.some(appointment => {
              const apptStart = new Date(appointment.startTime);
              const apptEnd = new Date(appointment.endTime);
              
              // Check if the slot overlaps with the appointment
              return (
                (currentTime < apptEnd && slotEndTime > apptStart) ||
                (currentTime.getTime() === apptStart.getTime()) ||
                (slotEndTime.getTime() === apptEnd.getTime())
              );
            });
            
            if (!isOverlapping) {
              slots.push({
                startTime: new Date(currentTime),
                endTime: new Date(slotEndTime)
              });
            }
            
            // Move to next slot (30-minute increments)
            currentTime.setMinutes(currentTime.getMinutes() + 30);
          }
          
          this.availableTimeSlots = slots;
        })
        .catch(error => {
          console.error('Error loading appointments:', error);
          this.error = t('appointments', 'Failed to load appointments');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    selectDate(date) {
      this.selectedDate = new Date(date);
      this.selectedTimeSlot = null;
      this.$emit('date-selected', this.selectedDate);
      
      if (this.appointmentType) {
        this.generateTimeSlots();
      }
    },
    
    selectTimeSlot(slot) {
      this.selectedTimeSlot = {
        startTime: new Date(slot.startTime),
        endTime: new Date(slot.endTime)
      };
      
      this.$emit('time-slot-selected', this.selectedTimeSlot);
    },
    
    previousWeek() {
      const newStart = new Date(this.currentWeekStart);
      newStart.setDate(newStart.getDate() - 7);
      this.currentWeekStart = newStart;
      
      // Clear selected date if it's not in the current week
      if (this.selectedDate && !this.isDateInCurrentWeek(this.selectedDate)) {
        this.selectedDate = null;
        this.selectedTimeSlot = null;
        this.$emit('date-selected', null);
        this.$emit('time-slot-selected', null);
      }
    },
    
    nextWeek() {
      const newStart = new Date(this.currentWeekStart);
      newStart.setDate(newStart.getDate() + 7);
      this.currentWeekStart = newStart;
      
      // Clear selected date if it's not in the current week
      if (this.selectedDate && !this.isDateInCurrentWeek(this.selectedDate)) {
        this.selectedDate = null;
        this.selectedTimeSlot = null;
        this.$emit('date-selected', null);
        this.$emit('time-slot-selected', null);
      }
    },
    
    getStartOfWeek(date) {
      const result = new Date(date);
      const day = result.getDay();
      const diff = result.getDate() - day + (day === 0 ? -6 : 1); // Adjust for Sunday
      result.setDate(diff);
      result.setHours(0, 0, 0, 0);
      return result;
    },
    
    isDateInCurrentWeek(date) {
      const checkDate = new Date(date);
      const weekStart = new Date(this.currentWeekStart);
      const weekEnd = new Date(weekStart);
      weekEnd.setDate(weekStart.getDate() + 6);
      weekEnd.setHours(23, 59, 59, 999);
      
      return checkDate >= weekStart && checkDate <= weekEnd;
    },
    
    getDayOfWeek(date) {
      const day = date.getDay();
      return day === 0 ? 7 : day; // Convert Sunday (0) to 7 for consistency
    },
    
    isSameDay(date1, date2) {
      return date1.getFullYear() === date2.getFullYear() &&
             date1.getMonth() === date2.getMonth() &&
             date1.getDate() === date2.getDate();
    },
    
    addDays(date, days) {
      const result = new Date(date);
      result.setDate(result.getDate() + days);
      return result;
    },
    
    formatDateRange(start, end) {
      const startMonth = start.toLocaleString('default', { month: 'short' });
      const endMonth = end.toLocaleString('default', { month: 'short' });
      
      if (startMonth === endMonth) {
        return `${startMonth} ${start.getDate()} - ${end.getDate()}, ${start.getFullYear()}`;
      } else {
        return `${startMonth} ${start.getDate()} - ${endMonth} ${end.getDate()}, ${start.getFullYear()}`;
      }
    },
    
    formatDayName(date) {
      return date.toLocaleString('default', { weekday: 'short' });
    },
    
    formatDayDate(date) {
      return date.getDate();
    },
    
    formatFullDate(date) {
      return date.toLocaleString('default', { weekday: 'long', month: 'long', day: 'numeric' });
    },
    
    formatTime(date) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    formatDateForApi(date) {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    },
    
    reset() {
      this.selectedDate = null;
      this.selectedTimeSlot = null;
      this.availableTimeSlots = [];
      this.$emit('date-selected', null);
      this.$emit('time-slot-selected', null);
    }
  }
};
</script>

<style scoped>
.date-time-selector {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 20px;
}

.date-time-selector__label {
  font-weight: bold;
  margin-bottom: 5px;
}

.date-time-selector__loading,
.date-time-selector__error {
  padding: 10px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.date-time-selector__content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.date-selector {
  border: 1px solid var(--color-border);
  border-radius: 5px;
  overflow: hidden;
}

.date-selector__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  background-color: var(--color-primary);
  color: var(--color-primary-text);
}

.date-selector__header button {
  background: none;
  border: none;
  color: var(--color-primary-text);
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.date-selector__header button:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

.date-selector__days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background-color: var(--color-background-hover);
}

.date-day {
  padding: 10px;
  text-align: center;
  cursor: pointer;
  border-right: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  transition: all 0.2s ease;
}

.date-day:nth-child(7n) {
  border-right: none;
}

.date-day:hover {
  background-color: var(--color-background-dark);
}

.date-day.active {
  background-color: var(--color-primary-light);
  color: var(--color-primary-element);
  font-weight: bold;
}

.date-day__name {
  font-size: 0.8em;
  margin-bottom: 5px;
}

.date-day__date {
  font-size: 1.2em;
}

.time-selector {
  border: 1px solid var(--color-border);
  border-radius: 5px;
  overflow: hidden;
}

.time-selector__header {
  padding: 10px 15px;
  background-color: var(--color-background-dark);
  font-weight: bold;
}

.time-selector__empty {
  padding: 20px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.time-selector__slots {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
  padding: 15px;
}

.time-slot {
  padding: 10px;
  text-align: center;
  background-color: var(--color-background-hover);
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.time-slot:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.time-slot.selected {
  background-color: var(--color-primary);
  color: var(--color-primary-text);
}
</style>
