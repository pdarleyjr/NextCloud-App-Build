<template>
  <div class="appointment-type-selector">
    <div class="appointment-type-selector__label">{{ t('appointments', 'Select Appointment Type') }}</div>
    
    <div v-if="loading" class="appointment-type-selector__loading">
      <span class="icon-loading"></span>
      {{ t('appointments', 'Loading appointment types...') }}
    </div>
    
    <div v-else-if="error" class="appointment-type-selector__error">
      {{ error }}
    </div>
    
    <div v-else>
      <div class="appointment-type-categories" v-if="categories.length > 0">
        <div 
          v-for="category in categories" 
          :key="category"
          class="appointment-type-category"
          :class="{ 'active': selectedCategory === category }"
          @click="selectedCategory = category"
        >
          {{ category }}
        </div>
      </div>
      
      <div class="appointment-type-list" v-if="filteredAppointmentTypes.length > 0">
        <div 
          v-for="type in filteredAppointmentTypes" 
          :key="type.id"
          class="appointment-type-item"
          :class="{ 'selected': selectedAppointmentType === type.id }"
          @click="selectAppointmentType(type)"
        >
          <div class="appointment-type-item__name">{{ type.name }}</div>
          <div class="appointment-type-item__details">
            <div class="appointment-type-item__duration">
              <span class="icon-history"></span>
              {{ type.duration }} {{ t('appointments', 'minutes') }}
            </div>
            <div class="appointment-type-item__price">
              <span class="icon-tag"></span>
              ${{ type.price.toFixed(2) }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="appointment-type-empty">
        {{ t('appointments', 'No appointment types available in this category.') }}
      </div>
    </div>
    
    <div v-if="selectedAppointmentTypeData" class="appointment-type-details">
      <h3>{{ selectedAppointmentTypeData.name }}</h3>
      <div class="appointment-type-details__info">
        <div class="appointment-type-details__duration">
          <span class="label">{{ t('appointments', 'Duration') }}:</span>
          <span class="value">{{ selectedAppointmentTypeData.duration }} {{ t('appointments', 'minutes') }}</span>
        </div>
        <div class="appointment-type-details__price">
          <span class="label">{{ t('appointments', 'Price') }}:</span>
          <span class="value">${{ selectedAppointmentTypeData.price.toFixed(2) }}</span>
        </div>
        <div class="appointment-type-details__buffer">
          <span class="label">{{ t('appointments', 'Buffer Before') }}:</span>
          <span class="value">{{ selectedAppointmentTypeData.packageBefore }} {{ t('appointments', 'minutes') }}</span>
        </div>
        <div class="appointment-type-details__buffer">
          <span class="label">{{ t('appointments', 'Buffer After') }}:</span>
          <span class="value">{{ selectedAppointmentTypeData.packageAfter }} {{ t('appointments', 'minutes') }}</span>
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
  name: 'AppointmentTypeSelector',
  
  props: {
    therapistId: {
      type: String,
      required: false,
      default: null
    }
  },
  
  data() {
    return {
      loading: true,
      error: null,
      appointmentTypes: {},
      categories: [],
      selectedCategory: null,
      selectedAppointmentType: null,
      selectedAppointmentTypeData: null
    };
  },
  
  computed: {
    filteredAppointmentTypes() {
      if (!this.selectedCategory || !this.appointmentTypes[this.selectedCategory]) {
        return [];
      }
      
      return this.appointmentTypes[this.selectedCategory];
    }
  },
  
  watch: {
    therapistId: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.loadAppointmentTypes();
        } else {
          this.reset();
        }
      }
    }
  },
  
  methods: {
    t,
    
    loadAppointmentTypes() {
      this.loading = true;
      this.error = null;
      
      axios.get(generateUrl('/apps/appointments/api/appointment-types'))
        .then(response => {
          this.appointmentTypes = response.data;
          this.categories = Object.keys(this.appointmentTypes);
          
          if (this.categories.length > 0) {
            this.selectedCategory = this.categories[0];
          }
        })
        .catch(error => {
          console.error('Error loading appointment types:', error);
          this.error = t('appointments', 'Failed to load appointment types');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    selectAppointmentType(type) {
      this.selectedAppointmentType = type.id;
      this.selectedAppointmentTypeData = type;
      this.$emit('appointment-type-selected', type);
    },
    
    reset() {
      this.selectedAppointmentType = null;
      this.selectedAppointmentTypeData = null;
      this.$emit('appointment-type-selected', null);
    }
  }
};
</script>

<style scoped>
.appointment-type-selector {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 20px;
}

.appointment-type-selector__label {
  font-weight: bold;
  margin-bottom: 5px;
}

.appointment-type-selector__loading,
.appointment-type-selector__error,
.appointment-type-empty {
  padding: 10px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.appointment-type-categories {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 15px;
}

.appointment-type-category {
  padding: 8px 15px;
  border-radius: 20px;
  background-color: var(--color-background-hover);
  cursor: pointer;
  transition: all 0.2s ease;
}

.appointment-type-category.active {
  background-color: var(--color-primary);
  color: var(--color-primary-text);
}

.appointment-type-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
}

.appointment-type-item {
  padding: 15px;
  border-radius: 5px;
  background-color: var(--color-background-hover);
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.appointment-type-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.appointment-type-item.selected {
  border-color: var(--color-primary);
  background-color: var(--color-primary-light);
}

.appointment-type-item__name {
  font-weight: bold;
  margin-bottom: 10px;
}

.appointment-type-item__details {
  display: flex;
  justify-content: space-between;
  color: var(--color-text-maxcontrast);
  font-size: 0.9em;
}

.appointment-type-details {
  margin-top: 20px;
  padding: 15px;
  background-color: var(--color-background-hover);
  border-radius: 5px;
  border-left: 4px solid var(--color-primary);
}

.appointment-type-details h3 {
  margin-top: 0;
  color: var(--color-primary);
}

.appointment-type-details__info {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.appointment-type-details__duration,
.appointment-type-details__price,
.appointment-type-details__buffer {
  display: flex;
  align-items: center;
}

.appointment-type-details__duration .label,
.appointment-type-details__price .label,
.appointment-type-details__buffer .label {
  font-weight: bold;
  margin-right: 5px;
}
</style>