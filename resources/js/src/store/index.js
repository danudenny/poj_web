import {createStore} from 'vuex'

// import 'es6-promise/auto';
import layout from './modules/layout';
import menu from './modules/menu';
import bootsrap from "./modules/bootsrap"
import timesheetSchedule from "./modules/timesheetSchedule"
// import todo from './modules/todo';
// import firebase_todo from './modules/firebase_todo';
// import common from './modules/common';
// import { users } from './modules/users';
import authentication from '../helpers/authentication';

const TOKEN_STORAGE_KEY = 'my_app_token';

export default createStore({
  state:{
      langIcon: '',
      langLangauge: '',
      isActive:false,
      token:localStorage.getItem(TOKEN_STORAGE_KEY) || null,
      user: null,
      avatar: '',
      roles: [],
      permissions: []
  },
  getters:{
    langIcon: (state)=>{ return state.langIcon},
    langLangauge:(state)=>{return state.langLangauge},
    isAuthenticated(state){
        return state.token != null;
    },
    roles: (state) => {
        return state.roles
    },
    permissions: (state) => {
        return state.permissions
    },
  },
  mutations: {
      changeLang (state, payload) {
        localStorage.setItem('currentLanguage', payload.id);
        localStorage.setItem('currentLanguageIcon', payload.icon);
        state.langIcon = payload.icon || 'flag-icon-us'
        state.langLangauge = payload.id || 'EN'
        // window.location.reload();
      },
      change(state){
        state.isActive = !state.isActive
      },

      setToken(state, token){
        state.token = token;
        localStorage.setItem(TOKEN_STORAGE_KEY, token);
      },
      setUser(state, user){
          state.user = user;
          localStorage.setItem('USER_STORAGE_KEY', JSON.stringify(user));
      },
      setRoles(state, roles) {
          state.roles = roles;
          localStorage.setItem('USER_ROLES', JSON.stringify(roles));
      },
      setPermissions(state, permissions) {
          state.permissions = permissions;
          localStorage.setItem('USER_PERMISSIONS', JSON.stringify(permissions));
      },
      setAvatar(state, user) {
          state.avatar = user.avatar;
          localStorage.setItem('USER_AVATAR', user.avatar);
      },
      clearToken(state){
        state.token = null;
        localStorage.removeItem(TOKEN_STORAGE_KEY);
      },
      clearUser(state){
          state.user = null;
          localStorage.removeItem('USER_STORAGE_KEY');
      },
      clearRoles (state)
      {
          state.roles = null;
          localStorage.removeItem('USER_ROLES');
      },
      clearPermissions (state)
      {
          state.permissions = null;
          localStorage.removeItem('USER_PERMISSIONS');
      },
      clearAvatar(state) {
          state.avatar = '';
          localStorage.removeItem('USER_AVATAR');
      },
    },
    actions: {
      setLang ({ commit }, payload) {
        commit('changeLang', payload);

      },
      async login ({ commit }, credentials) {
          try {
            const { token, user } = await authentication.login(credentials);
            commit('setToken', token);
            commit('setUser', user);
            commit('setRoles', user.roles);
            commit('setPermissions', user.permissions);
            commit('setAvatar', user);
          } catch (e) {
            commit('clearToken');
            localStorage.removeItem(TOKEN_STORAGE_KEY);
          }
      },
      async logout({ commit }) {
          try {
              // await authentication.logout();
              commit('clearToken');
              commit('clearUser');
              commit('clearRoles');
              commit('clearPermissions');
              commit('clearAvatar');
              localStorage.removeItem(TOKEN_STORAGE_KEY);
              localStorage.removeItem('USER_STORAGE_KEY');
              localStorage.removeItem('USER_AVATAR');
              console.log('Logout successfully');
          } catch (e) {
              console.log('Logout error:', e);
          }
      },
    },
    modules: {
      layout,
      menu,
      bootsrap,
      timesheetSchedule
    }
});

