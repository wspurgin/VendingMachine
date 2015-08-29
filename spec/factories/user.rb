FactoryGirl.define do
  factory :user do
    name                  { FFaker::Name.name }
    smu_id
    email                 { FFaker::Internet.email }
    group
    password              "asdfasdf"
    password_confirmation "asdfasdf"
  end

  sequence :smu_id do |n|
    id = 10000000 + n
    id.to_s
  end
end
