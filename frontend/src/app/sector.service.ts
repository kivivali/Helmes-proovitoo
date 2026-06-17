import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { Sector, Submission } from './sector';

const API = 'http://localhost:8000/api';
const CREDS = { withCredentials: true } as const;

@Injectable({ providedIn: 'root' })
export class SectorService {
  private http = inject(HttpClient);

  getSectors(): Observable<Sector[]> {
    return this.http.get<Sector[]>(`${API}/sectors`, CREDS);
  }

  // observe: 'response' lets us read r.body, which is null for 204 No Content.
  getSubmission(): Observable<Submission | null> {
    return this.http
      .get<Submission>(`${API}/submissions/me`, { ...CREDS, observe: 'response' })
      .pipe(map(r => r.body));
  }

  saveSubmission(payload: Submission): Observable<Submission> {
    return this.http.post<Submission>(`${API}/submissions`, payload, CREDS);
  }
}
