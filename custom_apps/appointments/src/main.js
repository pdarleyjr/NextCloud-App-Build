/* eslint-disable */
import { generateFilePath } from '@nextcloud/router'
import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { createPinia, PiniaVuePlugin } from 'pinia'
import App2 from './App2.vue'

// CSP config for webpack dynamic chunk loading
__webpack_nonce__ = btoa(OC.requestToken)

// Correct the root of the app for chunk loading
// OC.linkTo matches the apps folders
__webpack_public_path__ = generateFilePath('appointments', '', 'js/')

// Register global components
Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

// Initialize Pinia
Vue.use(PiniaVuePlugin)
const pinia = createPinia()

// Initialize Vue
document.addEventListener('DOMContentLoaded', () => {
	const appElement = document.getElementById('app-content-vue')
	if (appElement) {
		const app = new Vue({
			pinia,
			render: h => h(App2)
		})
		app.$mount(appElement)
	}
})
