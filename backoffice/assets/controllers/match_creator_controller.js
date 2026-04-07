import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = [
    'modal', 'phase', 'poule', 'round', 'tatami', 'timeStart', 'timeEnd',
    'participant1', 'participant2', 'equipe1', 'equipe2',
    'combattantsList', 'equipesList', 'form', 'confirmBtn'
  ];

  static values = {
    tournamentId: Number,
    isTeam: Boolean,
    rounds: Array,
    timeSlots: Array,
  };

  connect() {
    this.currentPhase = 'qualification';
    this.selectedParticipants = { p1: null, p2: null };
    this.selectedEquipes = { e1: null, e2: null };
    
    // Initialiser les event listeners pour les sélections
    this.setupSelectionHandlers();
  }

  setupSelectionHandlers() {
    // Event delegation pour les combattants
    const combattantsList = this.combattantsListTarget;
    if (combattantsList) {
      combattantsList.addEventListener('click', (e) => {
        if (e.target.closest('.combattant-card')) {
          e.preventDefault();
          this.toggleParticipant(e);
        }
      });
    }

    // Event delegation pour les équipes
    const equipesList = this.equipesListTarget;
    if (equipesList) {
      equipesList.addEventListener('click', (e) => {
        if (e.target.closest('.equipe-card')) {
          e.preventDefault();
          this.toggleEquipe(e);
        }
      });
    }
  }

  // ── Sélection de combattants/équipes ─────────────────────────────────────

  toggleParticipant(event) {
    const card = event.target.closest('.combattant-card');
    if (!card) return;

    const participantId = card.dataset.participantId;
    const slot = card.dataset.slot; // 'p1' ou 'p2'

    if (this.selectedParticipants[slot] === participantId) {
      // Déselectionner
      this.selectedParticipants[slot] = null;
      card.classList.remove('selected');
    } else {
      // Sélectionner et déselectionner l'ancien
      const currentList = card.closest('.combattants-list');
      currentList.querySelectorAll(`.combattant-card[data-slot="${slot}"].selected`).forEach(el => {
        el.classList.remove('selected');
      });
      
      this.selectedParticipants[slot] = participantId;
      card.classList.add('selected');
    }

    // Mettre à jour les champs cachés
    this.participant1Target.value = this.selectedParticipants.p1 || '';
    this.participant2Target.value = this.selectedParticipants.p2 || '';
    
    this.updateConfirmButton();
  }

  toggleEquipe(event) {
    const card = event.target.closest('.equipe-card');
    if (!card) return;

    const equipeId = card.dataset.equipeId;
    const slot = card.dataset.slot; // 'e1' ou 'e2'

    if (this.selectedEquipes[slot] === equipeId) {
      this.selectedEquipes[slot] = null;
      card.classList.remove('selected');
    } else {
      // Déselectionner l'ancien
      const currentList = card.closest('.equipes-list');
      currentList.querySelectorAll(`.equipe-card[data-slot="${slot}"].selected`).forEach(el => {
        el.classList.remove('selected');
      });
      
      this.selectedEquipes[slot] = equipeId;
      card.classList.add('selected');
    }

    // Mettre à jour les champs cachés
    this.equipe1Target.value = this.selectedEquipes.e1 || '';
    this.equipe2Target.value = this.selectedEquipes.e2 || '';
    
    this.updateConfirmButton();
  }

  // ── Sélection de tatami ──────────────────────────────────────────────────

  selectTatami(event) {
    const button = event.currentTarget;
    const tatami = button.dataset.tatami;
    
    // Déselectionner les autres
    button.parentElement.querySelectorAll('.tatami-option.selected').forEach(el => {
      if (el !== button) el.classList.remove('selected');
    });
    
    button.classList.toggle('selected');
    this.tatamisTarget.value = button.classList.contains('selected') ? tatami : '';
    this.updateConfirmButton();
  }

  // ── Sélection de créneaux horaires ─────────────────────────────────────

  selectTimeSlot(event) {
    const button = event.currentTarget;
    const time = button.dataset.time;
    const slot = button.dataset.slot; // 'start' ou 'end'
    
    // Déselectionner les autres dans le même groupe
    button.parentElement.querySelectorAll(`.time-slot[data-slot="${slot}"].selected`).forEach(el => {
      if (el !== button) el.classList.remove('selected');
    });
    
    button.classList.toggle('selected');
    
    if (slot === 'start') {
      this.timeStartTarget.value = button.classList.contains('selected') ? time : '';
    } else {
      this.timeEndTarget.value = button.classList.contains('selected') ? time : '';
    }
    
    this.updateConfirmButton();
  }

  // ── Soumission du formulaire ───────────────────────────────────────────────

  submitForm(event) {
    event.preventDefault();
    
    // Validation basique
    if (!this.isFormValid()) {
      alert('Veuillez sélectionner une poule et deux combattants/équipes.');
      return;
    }

    // Soumettre le formulaire
    this.formTarget.submit();
  }

  // ── Fermeture du modal ───────────────────────────────────────────────────

  closeModal() {
    this.modalTarget.classList.remove('modal-open');
    this.modalTarget.style.display = 'none';
    this.resetForm();
  }

  // ── Helpers ────────────────────────────────────────────────────────────────

  resetForm() {
    this.pouleTarget.value = '';
    this.roundTarget.value = '';
    this.tatamisTarget.value = '';
    this.timeStartTarget.value = '';
    this.timeEndTarget.value = '';
    
    this.selectedParticipants = { p1: null, p2: null };
    this.selectedEquipes = { e1: null, e2: null };
    
    this.participant1Target.value = '';
    this.participant2Target.value = '';
    this.equipe1Target.value = '';
    this.equipe2Target.value = '';
    
    // Déselectionner les cartes visuelles
    this.modalTarget.querySelectorAll('.combattant-card.selected, .equipe-card.selected, .tatami-option.selected, .time-slot.selected').forEach(el => {
      el.classList.remove('selected');
    });
    
    this.updateConfirmButton();
  }

  isFormValid() {
    const poule = this.pouleTarget.value;
    const hasParticipants = this.selectedParticipants.p1 && this.selectedParticipants.p2;
    const hasEquipes = this.selectedEquipes.e1 && this.selectedEquipes.e2;
    const hasSelection = this.isTeamValue ? hasEquipes : hasParticipants;
    
    if (this.phaseTarget.value === 'elimination') {
      return poule && this.roundTarget.value && hasSelection;
    }
    
    return poule && hasSelection;
  }

  updateConfirmButton() {
    const isValid = this.isFormValid();
    this.confirmBtnTarget.disabled = !isValid;
    this.confirmBtnTarget.style.opacity = isValid ? '1' : '0.5';
  }
}
