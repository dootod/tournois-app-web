package com.example.mobile;

import android.os.Bundle;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;

import com.example.mobile.api.ApiClient;
import com.example.mobile.model.Tournoi;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MesTournoisActivity extends BaseActivity {

    private final List<Tournoi> data = new ArrayList<>();
    private ArrayAdapter<Tournoi> adapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_tournois);
        setTitle("Mes inscriptions");

        ListView list = findViewById(R.id.list);
        adapter = new ArrayAdapter<Tournoi>(this, 0, data) {
            @NonNull
            @Override
            public View getView(int position, View convertView, @NonNull ViewGroup parent) {
                if (convertView == null) {
                    convertView = getLayoutInflater().inflate(R.layout.item_tournoi, parent, false);
                }
                Tournoi t = data.get(position);
                ((TextView) convertView.findViewById(R.id.title)).setText("Tournoi #" + t.id + " — " + t.date);
                ((TextView) convertView.findViewById(R.id.subtitle)).setText(
                    t.etat + (t.equipe ? " · équipe" : " · individuel"));
                Button btn = convertView.findViewById(R.id.btnAction);
                btn.setText("Se désinscrire");
                btn.setEnabled("ouvert".equals(t.etat));
                btn.setOnClickListener(v -> desinscrire(t));
                return convertView;
            }
        };
        list.setAdapter(adapter);
        load();
    }

    private void load() {
        ApiClient.service().getMesTournois().enqueue(new Callback<List<Tournoi>>() {
            @Override public void onResponse(Call<List<Tournoi>> call, Response<List<Tournoi>> r) {
                if (r.isSuccessful() && r.body() != null) {
                    data.clear();
                    data.addAll(r.body());
                    adapter.notifyDataSetChanged();
                    if (data.isEmpty()) {
                        Toast.makeText(MesTournoisActivity.this, "Aucune inscription", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    Toast.makeText(MesTournoisActivity.this, "Erreur " + r.code(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<List<Tournoi>> call, Throwable t) {
                Toast.makeText(MesTournoisActivity.this, "Réseau: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void desinscrire(Tournoi t) {
        ApiClient.service().desinscrire(t.id).enqueue(new Callback<Void>() {
            @Override public void onResponse(Call<Void> call, Response<Void> r) {
                if (r.isSuccessful()) {
                    Toast.makeText(MesTournoisActivity.this, "Désinscription effectuée", Toast.LENGTH_SHORT).show();
                    load();
                } else if (r.code() == 400) {
                    Toast.makeText(MesTournoisActivity.this, "Impossible : inscription déjà payée", Toast.LENGTH_LONG).show();
                } else {
                    Toast.makeText(MesTournoisActivity.this, "Erreur " + r.code(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<Void> call, Throwable t) {
                Toast.makeText(MesTournoisActivity.this, "Réseau: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
