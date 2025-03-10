<template>
  <div id="content" class="app-appointments">
    <div class="app-navigation-toggle" data-navigation="">
      <div class="icon-menu"></div>
    </div>
    
    <AppNavigation>
      <AppNavigationItem
        v-for="item in navigationItems"
        :key="item.id"
        :title="item.title"
        :icon="item.icon"
        :exact="item.exact"
        :to="item.to"
      />
    </AppNavigation>
    
    <AppContent>
      <div class="app-content-wrapper">
        <router-view></router-view>
      </div>
    </AppContent>
  </div>
</template>

<script>
import { NcAppContent, NcAppNavigation, NcAppNavigationItem } from '@nextcloud/vue';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'App',
  
  components: {
    AppContent: NcAppContent,
    AppNavigation: NcAppNavigation,
    AppNavigationItem: NcAppNavigationItem
  },
  
  data() {
    return {
      isTherapist: false,
      navigationItems: []
    };
  },
  
  mounted() {
    // Check if the user is a therapist
    this.checkTherapistStatus();
  },
  
  methods: {
    t,
    
    checkTherapistStatus() {
      // This would be replaced with an actual API call
      const userDataElement = document.getElementById('appointments-app');
      if (userDataElement) {
        this.isTherapist = userDataElement.dataset.isTherapist === 'true';
        this.updateNavigationItems();
      }
    },
    
    updateNavigationItems() {
      // Common navigation items
      this.navigationItems = [
        {
          id: 'appointments',
          title: t('appointments', 'Appointments'),
          icon: 'icon-calendar',
          to: { name: 'appointments' },
          exact: true
        },
        {
          id: 'invoices',
          title: t('appointments', 'Invoices'),
          icon: 'icon-details',
          to: { name: 'invoices' },
          exact: true
        }
      ];
      
      // Add therapist-specific navigation items
      if (this.isTherapist) {
        this.navigationItems.push(
          {
            id: 'schedule',
            title: t('appointments', 'Schedule'),
            icon: 'icon-calendar-dark',
            to: { name: 'schedule' },
            exact: true
          },
          {
            id: 'analytics',
            title: t('appointments', 'Analytics'),
            icon: 'icon-category-monitoring',
            to: { name: 'analytics' },
            exact: true
          }
        );
      } else {
        // Add client-specific navigation items
        this.navigationItems.push(
          {
            id: 'book',
            title: t('appointments', 'Book Appointment'),
            icon: 'icon-add',
            to: { name: 'book' },
            exact: true
          }
        );
      }
    }
  }
};
</script>

<style scoped>
.app-appointments {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.app-content-wrapper {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

@media (max-width: 768px) {
  .app-content-wrapper {
    padding: 10px;
  }
}
</style>