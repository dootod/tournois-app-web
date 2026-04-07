package com.example.mobile.util;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class Fmt {
    private static final SimpleDateFormat IN = new SimpleDateFormat("yyyy-MM-dd", Locale.FRANCE);
    private static final SimpleDateFormat OUT = new SimpleDateFormat("EEEE d MMMM yyyy", Locale.FRANCE);

    public static String dateFr(String iso) {
        if (iso == null || iso.length() < 10) return "";
        try {
            Date d = IN.parse(iso.substring(0, 10));
            String s = OUT.format(d);
            return s.substring(0, 1).toUpperCase(Locale.FRANCE) + s.substring(1);
        } catch (Exception e) {
            return iso;
        }
    }

    public static String etat(String etat) {
        if (etat == null) return "";
        switch (etat) {
            case "ouvert": return "Inscriptions ouvertes";
            case "ferme":
            case "fermé": return "Inscriptions fermées";
            case "en_cours": return "En cours";
            case "termine":
            case "terminé": return "Terminé";
            default: return etat;
        }
    }

    public static String phase(String phase, String round) {
        if (phase == null) return "";
        if ("qualification".equals(phase)) return "Qualification";
        return round != null ? round : "Éliminatoire";
    }
}
