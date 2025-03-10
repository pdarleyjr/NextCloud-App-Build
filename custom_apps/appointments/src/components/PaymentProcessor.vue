<template>
  <div class="payment-processor">
    <div class="payment-processor__label">{{ t('appointments', 'Payment Information') }}</div>
    
    <div v-if="loading" class="payment-processor__loading">
      <span class="icon-loading"></span>
      {{ t('appointments', 'Loading payment form...') }}
    </div>
    
    <div v-else-if="error" class="payment-processor__error">
      {{ error }}
    </div>
    
    <div v-else class="payment-processor__content">
      <div class="payment-summary">
        <h3>{{ t('appointments', 'Appointment Summary') }}</h3>
        
        <div class="payment-summary__details">
          <div class="payment-summary__item">
            <span class="label">{{ t('appointments', 'Appointment Type') }}:</span>
            <span class="value">{{ appointmentType.name }}</span>
          </div>
          
          <div class="payment-summary__item">
            <span class="label">{{ t('appointments', 'Date') }}:</span>
            <span class="value">{{ formatDate(timeSlot.startTime) }}</span>
          </div>
          
          <div class="payment-summary__item">
            <span class="label">{{ t('appointments', 'Time') }}:</span>
            <span class="value">{{ formatTime(timeSlot.startTime) }} - {{ formatTime(timeSlot.endTime) }}</span>
          </div>
          
          <div class="payment-summary__item">
            <span class="label">{{ t('appointments', 'Duration') }}:</span>
            <span class="value">{{ appointmentType.duration }} {{ t('appointments', 'minutes') }}</span>
          </div>
          
          <div class="payment-summary__item payment-summary__total">
            <span class="label">{{ t('appointments', 'Total') }}:</span>
            <span class="value">${{ appointmentType.price.toFixed(2) }}</span>
          </div>
        </div>
      </div>
      
      <div class="payment-form" v-if="!paymentComplete">
        <h3>{{ t('appointments', 'Payment Method') }}</h3>
        
        <div v-if="!squareApplicationId" class="payment-form__no-square">
          {{ t('appointments', 'Square payment integration is not configured. Please contact the administrator.') }}
        </div>
        
        <div v-else>
          <div id="card-container"></div>
          <div id="payment-status-container"></div>
          
          <button 
            class="primary payment-button" 
            @click="processPayment" 
            :disabled="isProcessing || !cardTokenized"
          >
            {{ isProcessing ? t('appointments', 'Processing...') : t('appointments', 'Pay Now') }}
          </button>
        </div>
      </div>
      
      <div v-else class="payment-success">
        <div class="icon-checkmark"></div>
        <h3>{{ t('appointments', 'Payment Successful') }}</h3>
        <p>{{ t('appointments', 'Your appointment has been booked and confirmed.') }}</p>
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
  name: 'PaymentProcessor',
  
  props: {
    therapistId: {
      type: String,
      required: true
    },
    appointmentType: {
      type: Object,
      required: true
    },
    timeSlot: {
      type: Object,
      required: true
    },
    squareApplicationId: {
      type: String,
      required: false,
      default: ''
    },
    squareEnvironment: {
      type: String,
      required: false,
      default: 'sandbox'
    }
  },
  
  data() {
    return {
      loading: true,
      error: null,
      isProcessing: false,
      paymentComplete: false,
      card: null,
      cardTokenized: false,
      paymentToken: null
    };
  },
  
  mounted() {
    if (this.squareApplicationId) {
      this.initializeSquare();
    } else {
      this.loading = false;
    }
  },
  
  methods: {
    t,
    
    initializeSquare() {
      // Load Square.js
      const script = document.createElement('script');
      script.src = this.squareEnvironment === 'sandbox' 
        ? 'https://sandbox.web.squarecdn.com/v1/square.js'
        : 'https://web.squarecdn.com/v1/square.js';
      script.onload = this.initializeCard;
      script.onerror = () => {
        this.error = t('appointments', 'Failed to load Square payment SDK');
        this.loading = false;
      };
      document.body.appendChild(script);
    },
    
    async initializeCard() {
      try {
        if (!window.Square) {
          throw new Error('Square.js failed to load properly');
        }
        
        const payments = window.Square.payments(this.squareApplicationId, this.squareEnvironment === 'sandbox');
        
        // Initialize card
        this.card = await payments.card();
        await this.card.attach('#card-container');
        
        // Add event listener for tokenization
        document.addEventListener('squareCardTokenized', this.handleCardTokenization);
        
        this.loading = false;
      } catch (error) {
        console.error('Error initializing Square card:', error);
        this.error = t('appointments', 'Failed to initialize payment form');
        this.loading = false;
      }
    },
    
    async handleCardTokenization(event) {
      if (event.detail.status === 'OK') {
        this.cardTokenized = true;
        this.paymentToken = event.detail.token;
      } else {
        this.cardTokenized = false;
        this.error = event.detail.errors[0].message;
      }
    },
    
    async processPayment() {
      if (!this.cardTokenized || !this.paymentToken) {
        // Try to tokenize the card
        try {
          this.isProcessing = true;
          const result = await this.card.tokenize();
          
          if (result.status === 'OK') {
            this.paymentToken = result.token;
          } else {
            throw new Error(result.errors[0].message);
          }
        } catch (error) {
          console.error('Error tokenizing card:', error);
          showError(t('appointments', 'Failed to process payment: ') + error.message);
          this.isProcessing = false;
          return;
        }
      }
      
      // Process payment with the backend
      try {
        // Create the appointment first
        const appointmentResponse = await axios.post(generateUrl('/apps/appointments/api/appointments'), {
          therapistId: this.therapistId,
          startTime: this.timeSlot.startTime.toISOString(),
          endTime: this.timeSlot.endTime.toISOString(),
          appointmentTypeId: this.appointmentType.id,
          title: this.appointmentType.name
        });
        
        const appointmentId = appointmentResponse.data.id;
        
        // Process payment
        await axios.post(generateUrl('/apps/appointments/api/billing/process-payment'), {
          appointmentId: appointmentId,
          nonce: this.paymentToken,
          amount: this.appointmentType.price
        });
        
        // Payment successful
        this.paymentComplete = true;
        showSuccess(t('appointments', 'Payment processed successfully!'));
        
        // Emit event for parent component
        this.$emit('payment-complete', appointmentResponse.data);
      } catch (error) {
        console.error('Error processing payment:', error);
        showError(t('appointments', 'Failed to process payment. Please try again.'));
      } finally {
        this.isProcessing = false;
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    
    formatTime(date) {
      return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  },
  
  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('squareCardTokenized', this.handleCardTokenization);
    
    // Clean up Square card
    if (this.card) {
      this.card.destroy();
    }
  }
};
</script>

<style scoped>
.payment-processor {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 20px;
}

.payment-processor__label {
  font-weight: bold;
  margin-bottom: 5px;
}

.payment-processor__loading,
.payment-processor__error {
  padding: 10px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.payment-processor__error {
  color: var(--color-error);
}

.payment-processor__content {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.payment-summary,
.payment-form,
.payment-success {
  padding: 20px;
  border-radius: 5px;
  background-color: var(--color-background-hover);
}

.payment-summary h3,
.payment-form h3 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--color-primary);
}

.payment-summary__details {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.payment-summary__item {
  display: flex;
  justify-content: space-between;
}

.payment-summary__item .label {
  font-weight: bold;
}

.payment-summary__total {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--color-border);
  font-size: 1.2em;
}

.payment-form__no-square {
  padding: 15px;
  text-align: center;
  color: var(--color-text-maxcontrast);
  background-color: var(--color-background-dark);
  border-radius: 5px;
}

#card-container {
  min-height: 100px;
  margin-bottom: 20px;
}

.payment-button {
  width: 100%;
  margin-top: 20px;
}

.payment-success {
  text-align: center;
  padding: 30px;
  background-color: var(--color-success-light);
  border: 1px solid var(--color-success);
}

.payment-success .icon-checkmark {
  font-size: 3em;
  color: var(--color-success);
  margin-bottom: 15px;
}

.payment-success h3 {
  color: var(--color-success);
}
</style>