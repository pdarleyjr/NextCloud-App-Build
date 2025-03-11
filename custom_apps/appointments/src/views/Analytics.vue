<template>
  <div class="analytics-view">
    <h2>{{ t('appointments', 'Analytics') }}</h2>
    
    <div class="filter-controls">
      <div class="date-range">
        <label>{{ t('appointments', 'Date Range') }}</label>
        <select v-model="dateRange" @change="loadAnalytics">
          <option value="week">{{ t('appointments', 'This Week') }}</option>
          <option value="month">{{ t('appointments', 'This Month') }}</option>
          <option value="quarter">{{ t('appointments', 'This Quarter') }}</option>
          <option value="year">{{ t('appointments', 'This Year') }}</option>
        </select>
      </div>
      
      <div class="view-toggle">
        <button 
          :class="{ active: viewMode === 'personal' }" 
          @click="viewMode = 'personal'; loadAnalytics()">
          {{ t('appointments', 'Personal') }}
        </button>
        <button 
          :class="{ active: viewMode === 'practice' }" 
          @click="viewMode = 'practice'; loadAnalytics()">
          {{ t('appointments', 'Practice') }}
        </button>
      </div>
    </div>
    
    <div v-if="loading" class="loading">
      <div class="icon-loading"></div>
      <span>{{ t('appointments', 'Loading analytics...') }}</span>
    </div>
    
    <div v-else-if="error" class="empty-content">
      <div class="icon-error"></div>
      <h3>{{ t('appointments', 'Error loading analytics') }}</h3>
      <p>{{ error }}</p>
    </div>
    
    <div v-else class="analytics-content">
      <!-- Summary Cards -->
      <div class="summary-cards">
        <div class="summary-card">
          <div class="summary-icon icon-calendar"></div>
          <div class="summary-data">
            <div class="summary-value">{{ analytics.totalAppointments }}</div>
            <div class="summary-label">{{ t('appointments', 'Appointments') }}</div>
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-icon icon-details"></div>
          <div class="summary-data">
            <div class="summary-value">${{ analytics.totalRevenue.toFixed(2) }}</div>
            <div class="summary-label">{{ t('appointments', 'Revenue') }}</div>
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-icon icon-user"></div>
          <div class="summary-data">
            <div class="summary-value">{{ analytics.uniqueClients }}</div>
            <div class="summary-label">{{ t('appointments', 'Clients') }}</div>
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-icon icon-time"></div>
          <div class="summary-data">
            <div class="summary-value">{{ analytics.averageDuration }} min</div>
            <div class="summary-label">{{ t('appointments', 'Avg. Duration') }}</div>
          </div>
        </div>
      </div>
      
      <!-- Charts Section -->
      <div class="charts-section">
        <div class="chart-container">
          <h3>{{ t('appointments', 'Appointments by Day') }}</h3>
          <div class="chart-placeholder">
            <!-- In a real app, this would be a chart component -->
            <div class="bar-chart">
              <div v-for="(value, day) in analytics.appointmentsByDay" :key="day" class="bar-chart-item">
                <div class="bar-label">{{ day }}</div>
                <div class="bar-container">
                  <div class="bar" :style="{ height: calculateBarHeight(value, getMaxValue(analytics.appointmentsByDay)) + '%' }"></div>
                </div>
                <div class="bar-value">{{ value }}</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="chart-container">
          <h3>{{ t('appointments', 'Revenue by Service Type') }}</h3>
          <div class="chart-placeholder">
            <!-- In a real app, this would be a chart component -->
            <div class="pie-chart-placeholder">
              <div v-for="(value, type) in analytics.revenueByType" :key="type" class="pie-segment">
                <div class="segment-color" :style="{ backgroundColor: getRandomColor(type) }"></div>
                <div class="segment-label">{{ type }}</div>
                <div class="segment-value">${{ value.toFixed(2) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Detailed Stats -->
      <div class="detailed-stats">
        <h3>{{ t('appointments', 'Performance Metrics') }}</h3>
        
        <table>
          <thead>
            <tr>
              <th>{{ t('appointments', 'Metric') }}</th>
              <th>{{ t('appointments', 'Current') }}</th>
              <th>{{ t('appointments', 'Previous') }}</th>
              <th>{{ t('appointments', 'Change') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ t('appointments', 'Appointment Completion Rate') }}</td>
              <td>{{ analytics.metrics.completionRate.current }}%</td>
              <td>{{ analytics.metrics.completionRate.previous }}%</td>
              <td :class="getChangeClass(analytics.metrics.completionRate.change)">
                {{ formatChange(analytics.metrics.completionRate.change) }}
              </td>
            </tr>
            <tr>
              <td>{{ t('appointments', 'Average Revenue per Appointment') }}</td>
              <td>${{ analytics.metrics.revenuePerAppointment.current.toFixed(2) }}</td>
              <td>${{ analytics.metrics.revenuePerAppointment.previous.toFixed(2) }}</td>
              <td :class="getChangeClass(analytics.metrics.revenuePerAppointment.change)">
                {{ formatChange(analytics.metrics.revenuePerAppointment.change) }}
              </td>
            </tr>
            <tr>
              <td>{{ t('appointments', 'Client Retention Rate') }}</td>
              <td>{{ analytics.metrics.retentionRate.current }}%</td>
              <td>{{ analytics.metrics.retentionRate.previous }}%</td>
              <td :class="getChangeClass(analytics.metrics.retentionRate.change)">
                {{ formatChange(analytics.metrics.retentionRate.change) }}
              </td>
            </tr>
            <tr>
              <td>{{ t('appointments', 'Booking Utilization') }}</td>
              <td>{{ analytics.metrics.utilization.current }}%</td>
              <td>{{ analytics.metrics.utilization.previous }}%</td>
              <td :class="getChangeClass(analytics.metrics.utilization.change)">
                {{ formatChange(analytics.metrics.utilization.change) }}
              </td>
            </tr>
          </tbody>
        </table>
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
  name: 'Analytics',
  
  data() {
    return {
      loading: true,
      error: null,
      dateRange: 'month',
      viewMode: 'personal',
      analytics: {
        totalAppointments: 0,
        totalRevenue: 0,
        uniqueClients: 0,
        averageDuration: 0,
        appointmentsByDay: {},
        revenueByType: {},
        metrics: {
          completionRate: { current: 0, previous: 0, change: 0 },
          revenuePerAppointment: { current: 0, previous: 0, change: 0 },
          retentionRate: { current: 0, previous: 0, change: 0 },
          utilization: { current: 0, previous: 0, change: 0 }
        }
      },
      colorCache: {}
    };
  },
  
  mounted() {
    this.loadAnalytics();
  },
  
  methods: {
    t,
    
    loadAnalytics() {
      this.loading = true;
      this.error = null;
      
      const endpoint = this.viewMode === 'personal' 
        ? '/apps/appointments/api/appointments/analytics'
        : '/apps/appointments/api/appointments/analytics/practice';
      
      axios.get(generateUrl(endpoint), {
        params: { range: this.dateRange }
      })
        .then(response => {
          this.analytics = response.data;
        })
        .catch(error => {
          console.error('Error loading analytics:', error);
          this.error = t('appointments', 'Failed to load analytics data');
        })
        .finally(() => {
          this.loading = false;
          
          // If no data is returned, use mock data for demonstration
          if (!this.analytics.totalAppointments) {
            this.generateMockData();
          }
        });
    },
    
    generateMockData() {
      // Mock data for demonstration purposes
      this.analytics = {
        totalAppointments: 42,
        totalRevenue: 3850.00,
        uniqueClients: 18,
        averageDuration: 55,
        appointmentsByDay: {
          'Mon': 8,
          'Tue': 10,
          'Wed': 7,
          'Thu': 9,
          'Fri': 6,
          'Sat': 2,
          'Sun': 0
        },
        revenueByType: {
          'Initial Consultation': 1200.00,
          'Follow-up Session': 1650.00,
          'Group Therapy': 600.00,
          'Emergency Session': 400.00
        },
        metrics: {
          completionRate: { current: 92, previous: 88, change: 4 },
          revenuePerAppointment: { current: 91.67, previous: 85.50, change: 6.17 },
          retentionRate: { current: 78, previous: 72, change: 6 },
          utilization: { current: 85, previous: 80, change: 5 }
        }
      };
    },
    
    calculateBarHeight(value, maxValue) {
      return maxValue > 0 ? (value / maxValue) * 100 : 0;
    },
    
    getMaxValue(obj) {
      return Math.max(...Object.values(obj));
    },
    
    getRandomColor(key) {
      // Use a consistent color for the same key
      if (!this.colorCache[key]) {
        const colors = [
          '#4285F4', '#EA4335', '#FBBC05', '#34A853', 
          '#FF6D01', '#46BDC6', '#7B61FF', '#1E8E3E'
        ];
        this.colorCache[key] = colors[Object.keys(this.colorCache).length % colors.length];
      }
      return this.colorCache[key];
    },
    
    getChangeClass(change) {
      return change > 0 ? 'positive-change' : change < 0 ? 'negative-change' : '';
    },
    
    formatChange(change) {
      const prefix = change > 0 ? '+' : '';
      return `${prefix}${change.toFixed(1)}%`;
    }
  }
};
</script>

<style scoped>
.analytics-view {
  max-width: 1200px;
  margin: 0 auto;
}

.filter-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 20px 0;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 10px;
}

.view-toggle {
  display: flex;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  overflow: hidden;
}

.view-toggle button {
  background: var(--color-background-dark);
  border: none;
  padding: 8px 16px;
  cursor: pointer;
}

.view-toggle button.active {
  background: var(--color-primary-element);
  color: var(--color-primary-text);
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

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.summary-card {
  background-color: var(--color-background-hover);
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.summary-icon {
  font-size: 2em;
  margin-right: 15px;
  color: var(--color-primary-element);
}

.summary-value {
  font-size: 1.8em;
  font-weight: bold;
  margin-bottom: 5px;
}

.summary-label {
  color: var(--color-text-maxcontrast);
}

.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 30px;
  margin-bottom: 30px;
}

.chart-container {
  background-color: var(--color-background-hover);
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-placeholder {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bar-chart {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  height: 250px;
  width: 100%;
}

.bar-chart-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.bar-label {
  margin-bottom: 10px;
}

.bar-container {
  width: 30px;
  height: 200px;
  display: flex;
  align-items: flex-end;
}

.bar {
  width: 100%;
  background-color: var(--color-primary-element);
  border-radius: 4px 4px 0 0;
}

.bar-value {
  margin-top: 10px;
  font-weight: bold;
}

.pie-chart-placeholder {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.pie-segment {
  display: flex;
  align-items: center;
  padding: 10px;
}

.segment-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  margin-right: 10px;
}

.segment-label {
  flex: 1;
}

.segment-value {
  font-weight: bold;
}

.detailed-stats {
  background-color: var(--color-background-hover);
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

th {
  background-color: var(--color-background-dark);
  font-weight: bold;
}

.positive-change {
  color: var(--color-success);
}

.negative-change {
  color: var(--color-error);
}

@media (max-width: 768px) {
  .charts-section {
    grid-template-columns: 1fr;
  }
  
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .filter-controls {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
}
</style>