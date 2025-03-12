import { generateFilePath } from '@nextcloud/router'
import Vue from 'vue'
import VueRouter from 'vue-router'
import { translate, translatePlural } from '@nextcloud/l10n'
import App from './components/App.vue'

// Load routes
import BookAppointment from './views/BookAppointment.vue'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

// Correct the root of the app for chunk loading
// OC.linkTo matches the apps folders
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('appointments', '', 'js/')

// Register global components
Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

Vue.use(VueRouter)

// Create routes
const routes = [
  {
    path: '/',
    redirect: '/appointments'
  },
  {
    path: '/appointments',
    name: 'appointments',
    component: () => import('./views/AppointmentsList.vue')
  },
  {
    path: '/invoices',
    name: 'invoices',
    component: () => import('./views/InvoicesList.vue')
  },
  {
    path: '/book',
    name: 'book',
    component: BookAppointment
  },
  {
    path: '/schedule',
    name: 'schedule',
    component: () => import('./views/Schedule.vue')
  },
  {
    path: '/analytics',
    name: 'analytics',
    component: () => import('./views/Analytics.vue')
  },
  {
    path: '/invoices/create/:appointmentId',
    name: 'create-invoice',
    component: () => import('./views/CreateInvoice.vue'),
    props: true
  }
]

const router = new VueRouter({
  mode: 'history',
  base: generateFilePath('appointments', '', ''),
  routes
})

// Initialize Vue
document.addEventListener('DOMContentLoaded', () => {
  const appElement = document.getElementById('appointments-app')
  if (appElement) {
    // Get user data from the DOM
    const isTherapist = appElement.dataset.isTherapist === 'true'
    const userId = appElement.dataset.userId
    const squareEnvironment = appElement.dataset.squareEnvironment
    const squareApplicationId = appElement.dataset.squareApplicationId

    // Create Vue app
    const app = new Vue({
      router,
      render: h => h(App),
      data: {
        isTherapist,
        userId,
        squareEnvironment,
        squareApplicationId
      }
    })
    app.$mount(appElement)
  }
})
