Usage: 
```bash
$ app gorgo:entity:translations:dump "Oro\Bundle\ContactBundle\Entity\Contact"
or
$ app gorgo:entity:translations:dump "OroContactBundle:Contact"
```

Sample output:
```
oro:
    contact:
        entity_label: Contact
        entity_plural_label: Contacts
        birthday:
            label: Birthday
        description:
            label: Description
        facebook:
            label: Facebook
        fax:
            label: Fax
        first_name:
            label: 'First name'
        gender:
            label: Gender
        google_plus:
            label: Google+
        id:
            label: Id
        identification_number:
            label: 'Identification number'
        job_title:
            label: 'Job Title'
        last_name:
            label: 'Last name'
        linked_in:
            label: LinkedIn
        middle_name:
            label: 'Middle name'
        name_prefix:
            label: 'Name prefix'
        name_suffix:
            label: 'Name suffix'
        skype:
            label: Skype
        twitter:
            label: Twitter
    activity_contact:
        ac_contact_count:
            label: 'Total times contacted'
        ac_contact_count_in:
            label: 'Total number of incoming contact attempts'
        ac_contact_count_out:
            label: 'Total number of outgoing contact attempts'
        ac_last_contact_date:
            label: 'Last contact datetime'
        ac_last_contact_date_in:
            label: 'Last incoming contact datetime'
        ac_last_contact_date_out:
            label: 'Last outgoing contact datetime'
    ui:
        created_at: 'Created At'
        updated_at: 'Updated At'
```
